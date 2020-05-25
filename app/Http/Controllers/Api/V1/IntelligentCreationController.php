<?php
/**
 * Created by PhpStorm.
 * User: linzijie
 * Date: 2019-08-27
 * Time: 14:15
 */

namespace App\Http\Controllers\Api\V1;


use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\OutputMsg;
use App\Http\Controllers\Utils\TraitTVMSearch;
use App\Logics\BaiduOpenPlatfrom\NLP\BaiduNLP;
use App\Logics\IntelligentCreation\CustomConfig;
use App\Models\IntelligentWriting;
use App\Models\IntelligentWritingBgMusic;
use App\Models\IntelligentWritingTtsPer;
use App\Models\SensitiveWord;
use App\Models\UserResource;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class IntelligentCreationController extends ApiController
{

    use TraitTVMSearch;


    protected function prepare(Request $request)
    {
        parent::prepare($request);

        $this->not_check_sign_actions = ['uploadUserResource','uploadBgMusic'];
    }


    /**
     * @SWG\Post(
     *     path="/intelligent-creation/analysis-baijiahao-article-by-url",
     *     tags={"智能创作"},
     *     summary="抓取文章图片和文本",
     *      security={
     *          {
     *              "Bearer":{}
     *          }
     *      },
     *     @SWG\Parameter(
     *          in="body",
     *          name="data",
     *          description="",
     *          required=true,
     *          @SWG\Schema(
     *              type="object",
     *              @SWG\Property(property="url", type="string",description="网址"),
     *          )
     *      ),
     *      @SWG\Response(
     *          response=200,
     *          description="",
     *          @SWG\Schema(
     *              type="object",
     *              @SWG\Property(property="code", type="string",description="状态码"),
     *              @SWG\Property(property="msg", type="string",description="提示信息"),
     *                  @SWG\Property(property="data", type="array",
     *                      @SWG\Items(type="object",
     *                          @SWG\Property(property="title", type="integer",description="文章标题"),
     *                          @SWG\Property(property="news_summary", type="integer",description="新闻摘要"),
     *                          @SWG\Property(property="image_list", type="string",description="图片列表"),
     *                          @SWG\Property(property="lexer", type="string",description="文章关键词"),
     *                          @SWG\Property(property="article_url", type="string",description="原文链接")
     *                      ),
     *                  ),
     *          )
     *      ),
     * )
     */

    public function analysisBaiJiaHaoArticleByUrl()
    {
        $this->rule([
            'url' => 'required',
        ]);

        $article_url = $this->data('url');
        $dom = file_get_contents($article_url);
        $html_dom = new \HtmlParser\ParserDom($dom);
        $title = $html_dom->find("div.article-title h2", 0)->getPlainText();
        $content_text = $html_dom->find("div.article-content p");
        $content_images = $html_dom->find("div.article-content div.img-container img");

        $text_list = [];
        $image_list = [];


        collect($content_text)->each(function ($ele) use (&$text_list) {
            $text = trim($ele->getPlainText());
            if ($text != "") {
                $text_list[] = $text;
            }
        });


        collect($content_images)->each(function ($ele) use (&$image_list) {
            $src = $ele->getAttr('src');
            $image_list[] = $src;
        });


        $origin_text = implode('', $text_list);

        $nlp = new BaiduNLP();
        $origin_news_summary = $nlp->newsSummary($title, $origin_text, 360);
        $origin_lexer = $nlp->getLexer($origin_text);

        $filter_lexer = [];
        collect($origin_lexer['items'])->each(function ($item) use (&$filter_lexer) {
            if (in_array($item['ne'], ['PER', 'LOC', 'ORG']) || in_array($item['pos'],
                    ['nr', 'ns', 'nt', 'nw', 'nz'])) {
                if (!array_key_exists($item['item'], $filter_lexer)) {
                    $filter_lexer[$item['item']] = $item;
                }
            }
        });


        return $this->toJson([
            'title' => $title,
            'news_summary' => $origin_news_summary['summary'],
            'image_list' => $image_list,
            'lexer' => array_values($filter_lexer),
            'article_url' => $article_url
        ]);
    }


    /**
     * @SWG\Post(
     *      path="/intelligent-creation/upload-user-resource",
     *      tags={"智能创作"},
     *      summary="用户上传本地资源",
     *      security={
     *          {
     *              "Bearer":{}
     *          }
     *      },
     *      @SWG\Parameter(in="formData", name="type", type="string", required=true, description="资源类型 video|image"),
     *      @SWG\Parameter(in="formData", name="resource", type="file", required=true, description="资源文件"),
     *      @SWG\Parameter(in="formData", name="duration", type="integer", required=true, description="当文件为视频时，视频的时长（毫秒）"),
     *      @SWG\Response(
     *          response=200,
     *          description="",
     *          @SWG\Schema(
     *              type="object",
     *              @SWG\Property(property="code", type="string",description="状态码"),
     *              @SWG\Property(property="msg", type="string",description="提示信息"),
     *          )
     *      ),
     * )
     */
    public function uploadUserResource(Request $request)
    {
        $this->rule([
            'resource' => 'required|file|max:102400',
            'type' => 'required|in:' . join(',', UserResource::constants('TYPE')),
            'duration' => 'required_if:type,' . UserResource::TYPE_视频,
        ]);

        $user = $this->user();

        $type = $request->type;
        $duration = $request->duration;
        $resource = $request->file('resource');
        $save_method = "put_file_{$type}_path";
        $filePath = $resource->store($save_method('user_resource'));
        if ($filePath === false) {
            return $this->toJson(OutputMsg::UPLOAD_FILE_FAIL);
        }

        $user_resource = new UserResource();
        $user_resource->user_id = $user->id;
        $user_resource->file_path = $filePath;
        $user_resource->type = $type;


        if (file_exists($realFilePath = \Storage::path($filePath))) {
            $startMs = 1;
            $imgPath = put_file_image_path('user_resource') . '/' . getRandID(40) . '.jpg';
            $imgRealPath = \Storage::path($imgPath);
            $cmd = 'ffmpeg -ss ' . $startMs . '  -i ' . $realFilePath . ' -y  -vframes 1 ' . $imgRealPath;
            shell_exec($cmd);
            if (file_exists($imgRealPath)) {
                $user_resource->cover_pic = $imgPath;
            }
        }
        $user_resource->filesize = $resource->getSize();
        $type == UserResource::TYPE_视频 && $user_resource->duration = $duration;
        $user_resource->save();

        return $this->successMessage('上传成功');
    }


    /**
     * @SWG\Get(
     *      path="/intelligent-creation/user-resource-list",
     *      tags={"智能创作"},
     *      summary="用户本地素材列表",
     *      security={
     *          {
     *              "Bearer":{}
     *          }
     *      },
     *      @SWG\Parameter(in="query",name="page",description="当前页码",required=false,type="integer",),
     *      @SWG\Parameter(in="query",name="limit",description="每页数据量"  ,required=false,type="integer",),
     *      @SWG\Response(
     *          response=200,
     *          description="请求成功",
     *          @SWG\Schema(
     *              type="object",
     *              @SWG\Property(property="code", type="string",description="状态码"),
     *              @SWG\Property(property="msg", type="string",description="提示信息"),
     *              @SWG\Property(property="data", type="object",
     *                  @SWG\Property(property="page", type="string",description="当前页码"),
     *                  @SWG\Property(property="limit", type="string",description="每页数据条数"),
     *                  @SWG\Property(property="last_page", type="string",description="最后一页"),
     *                      @SWG\Property(property="list", type="array",
     *                              @SWG\Items(type="object",
     *                              @SWG\Property(property="id", type="string",description="资源id"),
     *                              @SWG\Property(property="type", type="string",description="类型 video|image"),
     *                              @SWG\Property(property="resource_url", type="string",description="资源链接"),
     *                              @SWG\Property(property="cover_pic", type="string",description="当type是video时 该字段为视频封面图"),
     *                              @SWG\Property(property="duration_str", type="string",description="时长字符串"),
     *                      ),
     *                  ),
     *               ),
     *
     *          )
     *      ),
     * )
     */

    public function userResourceList()
    {
        $user = $this->user();
        $page = $this->query('page') ?? 1;
        $limit = $this->query('limit') ?? 20;

        $userResourceQuery = UserResource::whereUserId($user->id);

        $paginate = $userResourceQuery->paginate($limit);
        $last_page = $paginate->lastPage();
        $total = $paginate->total();
        $list = $paginate->getCollection()->map(function ($userResource) {
            return [
                'id' => $userResource->id,
                'type' => $userResource->type,
                'resource_url' => \Storage::url($userResource->file_path),
                'cover_pic' => $userResource->cover_pic ? \Storage::url($userResource->cover_pic) : null,
                'duration_str' => microSecToTimeStr($userResource->duration)
            ];
        });

        $data = [
            'page' => $page,
            'limit' => $limit,
            'last_page' => $last_page,
            'total' => $total,
            'list' => $list
        ];
        return $this->toJson($data);

    }


/*  tracks	是	array	视频或图片素材数组，总时长范围为10-300秒
    +media_path	是	string	素材链接
    +start	是	float	片头视频后时间轴中的起始时间，从0开始。单位：秒，小数点后最多三位
    +duration	是	float	素材时长，单位：秒，小数点后最多三位
    +type	是	string	素材类型，支持“video”、“image”

*/


    public function create_timeline_task()
    {
        $this->rule([
            'title' => 'required',
            'tts_per_id' => 'required|int',
            'bg_music_id' => 'required|int',
            'video_logo_type' => 'required|in:' . join(',', IntelligentWriting::constants('VIDEO_LOGO_TYPE')),
            'video_logo_user_res_id' => 'required_if:video_logo_type,' . IntelligentWriting::VIDEO_LOGO_TYPE_有 . '|int',
            'video_begin_type' => 'required|in:' . join(',', IntelligentWriting::constants('VIDEO_BEGIN_TYPE')),
            'video_begin_user_res_id' => 'required_if:video_logo_type,' . IntelligentWriting::VIDEO_BEGIN_TYPE_上传片头 . '|int',
            'video_end_type' => 'required|in:' . join(',', IntelligentWriting::constants('VIDEO_END_TYPE')),
            'video_end_user_res_id' => 'required_if:video_logo_type,' . IntelligentWriting::VIDEO_END_TYPE_上传片尾 . '|int',
            'tracks' => 'required|array',
            'caption_tracks' => 'required|array',
        ]);

        /*$track = [
            'resource_type' => "image|video",
            'start' => 'ms',
            'duration' => 'ms',
            'sub_type' => '1,2',
            'resource_detail_video_1' => [
                'uuid' => 'uuid',
                'video_url' => 'url',
                'start_ms' =>'ms',
                'end_ms' => 'ms',
            ],
            'resource_detail_video_2' => [
                'user_resource_id' => 'id',
            ],

            'resource_detail_image_1' => [
                'image_url' => 'url'
            ],
            'resource_detail_image_2' => [
                'user_resource_id' => 'id'
            ]

        ];*/


        $title = $this->data('title');
        $tts_per_id = $this->data('tts_per_id');
        $bg_music_id = $this->data('bg_music_id');
        $video_logo_type = $this->data('video_logo_type');
        $video_logo_user_res_id = $this->data('video_logo_user_res_id');
        $video_begin_type = $this->data('video_begin_type');
        $video_begin_user_res_id = $this->data('video_begin_user_res_id');
        $video_end_type = $this->data('video_end_type');
        $video_end_user_res_id = $this->data('video_end_user_res_id');
        $tracks = $this->data('tracks');
        $caption_tracks = $this->data('caption_tracks');

        $intelligent = new IntelligentWriting();
        $intelligent->title = $title;
        $intelligent->tts_per_id = $tts_per_id;
        $intelligent->bg_music_id = $bg_music_id;


        //拿到所有用到的 用户素材
        $user_resource_ids = [];
        $video_logo_type == IntelligentWriting::VIDEO_LOGO_TYPE_有 && $user_resource_ids[] = $video_logo_user_res_id;
        $video_begin_type == IntelligentWriting::VIDEO_BEGIN_TYPE_上传片头 && $user_resource_ids[] = $video_begin_user_res_id;
        $video_end_type == IntelligentWriting::VIDEO_END_TYPE_上传片尾 && $user_resource_ids[] = $video_end_user_res_id;
        foreach ($tracks as $item){

            if($item['sub_type'] == 2) { //用户本地上传资源
                $user_resource_ids[] = $item['resource_detail']['user_resource_id'];
            }
        }

        $userResources = UserResource::find($user_resource_ids)->keyBy('id');

        $customConfig = CustomConfig::createFrom();

        $custom_config = [
            'video_logo' => [
                'type' => $video_logo_type,
                'logo'
            ]
        ];







    }





    /**
     * @SWG\Get(
     *      path="/intelligent-creation/list-of-options",
     *      tags={"智能创作"},
     *      summary="效果选择选项列表",
     *      security={
     *          {
     *              "Bearer":{}
     *          }
     *      },
     *      @SWG\Response(
     *          response=200,
     *          description="请求成功",
     *          @SWG\Schema(
     *              type="object",
     *              @SWG\Property(property="code", type="string",description="状态码"),
     *              @SWG\Property(property="msg", type="string",description="提示信息"),
     *              @SWG\Property(property="data", type="object",
     *                      @SWG\Property(property="bg_music", type="array",
     *                              @SWG\Items(type="object",
     *                                  @SWG\Property(property="built_in", type="string",description="内置音乐列表"),
     *                                  @SWG\Property(property="custom", type="string",description="本地音乐列表"),
     *                              ),
     *                      ),
     *                      @SWG\Property(property="tts_per", type="array",
     *                              @SWG\Items(type="object",
     *                                  @SWG\Property(property="tts_per_id", type="string",description="tts_per_id"),
     *                                  @SWG\Property(property="sex", type="string",description="1男声 2女声"),
     *                                  @SWG\Property(property="name", type="string",description="名字"),
     *                                  @SWG\Property(property="demo_sound_url", type="string",description="声音demo"),
     *                              ),
     *                      ),
     *
     *               ),
     *
     *          )
     *      ),
     * )
     */

    public function listOfOptions()
    {
        $user = $this->user();

        $bg_list = IntelligentWritingBgMusic::whereIsBuiltIn(1)->orWhere('user_id', $user->id)->get()->map(function (
            $bg_music
        ) {
            return [
                'id' => $bg_music->id,
                'name' => $bg_music->name,
                'category_name' => $bg_music->category_name,
                'audio_url' => \Storage::url($bg_music->audio_path),
                'is_built_in' => $bg_music->is_built_in,
            ];
        })->groupBy('is_built_in');


        $tts_per_list = IntelligentWritingTtsPer::get()->map(function ($tts_per) {
            return [
                'tts_per_id' => $tts_per->id,
                'sex' => $tts_per->sex,
                'name' => $tts_per->name,
                'demo_sound_url' => \Storage::url($tts_per->demo_sound_path),
            ];
        });

        return $this->toJson([
            'bg_music' => [
                'built_in' => $bg_list[1],
                'custom' => $bg_list[0],
            ],
            'tts_per' => $tts_per_list,
        ]);

    }


    /**
     * @SWG\Post(
     *      path="/intelligent-creation/upload-bg-music",
     *      tags={"智能创作"},
     *      summary="用户上传本地音乐",
     *      security={
     *          {
     *              "Bearer":{}
     *          }
     *      },
     *      @SWG\Parameter(in="formData", name="resource", type="file", required=true, description="音频文件"),
     *      @SWG\Response(
     *          response=200,
     *          description="",
     *          @SWG\Schema(
     *              type="object",
     *              @SWG\Property(property="code", type="string",description="状态码"),
     *              @SWG\Property(property="msg", type="string",description="提示信息"),
     *              @SWG\Property(property="data", type="object",
     *                  @SWG\Property(property="bg_music_id", type="integer",description="背景音乐id"),
     *              )
     *          )
     *      ),
     * )
     */
    public function uploadBgMusic(Request $request)
    {
        $this->rule([
            'title' => 'required',
            'resource' => 'required|file|max:2048',
        ]);

        $user = $this->user();

        $title = $request->file('title');
        $resource = $request->file('resource');
        $filePath = $resource->store(put_file_audio_path('user_resource'));
        if ($filePath === false) {
            return $this->toJson(OutputMsg::UPLOAD_FILE_FAIL);
        }

        $bg_music = new IntelligentWritingBgMusic();
        $bg_music->name = $title;
        $bg_music->audio_path = $filePath;
        $bg_music->is_built_in = 0;
        $bg_music->user_id = $user->id;
        $bg_music->save();
        return $this->toJson([
            'bg_music_id' => $bg_music->id,
        ]);
    }


    /**
     * @SWG\Get(
     *      path="/intelligent-creation/video-search",
     *      tags={"智能创作"},
     *      summary="帧搜索",
     *      security={
     *          {
     *              "Bearer":{}
     *          }
     *      },
     *      @SWG\Parameter(in="query",name="q",description="搜索关键字",required=true,type="string",),
     *      @SWG\Parameter(in="query",name="page",description="当前页码",required=false,type="integer",),
     *      @SWG\Parameter(in="query",name="limit",description="每页数据量"  ,required=false,type="integer",),
     *      @SWG\Response(
     *          response=200,
     *          description="请求成功",
     *          @SWG\Schema(
     *              type="object",
     *              @SWG\Property(property="code", type="string",description="状态码"),
     *              @SWG\Property(property="msg", type="string",description="提示信息"),
     *                  @SWG\Property(property="data", type="array",
     *                      @SWG\Items(type="object",
     *                          @SWG\Property(property="uuid", type="string",description="视频唯一标识码"),
     *                          @SWG\Property(property="title", type="string",description="标题"),
     *                          @SWG\Property(property="item_name", type="string",description="栏目名字"),
     *                          @SWG\Property(property="duration_str", type="string",description="视频时长"),
     *                          @SWG\Property(property="publish_time", type="string",description="播出时间"),
     *                          @SWG\Property(property="cp_name", type="string",description="频道名字"),
     *                          @SWG\Property(property="video_url", type="string",description="视频链接"),

     *                          @SWG\Property(property="time_start_str", type="string",description="开始时间str"),
     *                          @SWG\Property(property="time_start", type="string",description="开始时间（秒）"),
     *                          @SWG\Property(property="image", type="string",description="图片链接"),
     *                          @SWG\Property(property="brief", type="string",description="标签"),
     *                          @SWG\Property(property="ai_type", type="string",description="ai类型 asr/ocr"),
     *                      ),
     *                  ),
     *          )
     *      ),
     * )
     */


    public function getSearch()
    {
        $this->rule([
                'q' => 'required',
            ]
        );
        $q = $this->query('q');
        $page = $this->query('page') ?? 1;
        $limit = $this->query('limit') ?? 20;


        if (in_array($this->query('q'), SensitiveWord::getCachedSensitiveWords())) {
            return $this->toJson([
                'items' => [],
                'page_info' => [
                    'page' => $this->query('page') ?? 1,
                    'limit' => $this->query('limit') ?? 20,
                    'more' => false,
                    'total' => 0,
                ]
            ]);
        }

        if (!Str::contains($q, ' ')) {
            $q = '"' . $q . '"';
        }
        $params = [
            'q' => "@(text_ocr,text_asr) {$q}",
            'sortby' => urlencode("published DESC"),
            'hit_start' => ($page - 1) * $limit,
            'hit_size' => $limit,
        ];

        $body = $this->getTVMSearchHttpQueryStr($params, []);
        $url = config('video.tvm_search.base_url') . '/tse/v1/doc/query?fields=files/name:cover,video,video_hd||props/name:chan,prog||media/duration&field_match_size=10&mode=4&';// . $query_str;
        $data = $this->searchRequest($url, $body);


        if ($data && $data['kind'] == 'DocList' && isset($data['status']['hit_total']) && isset($data['items'])) {
            $total = $data['status']['hit_total'];
            $search_list = collect($data['items'])
                ->map(function ($play) {
                    $play['files'] = array_combine(array_column($play['files'], 'name'), $play['files']);
                    $play['props'] = array_combine(array_column($play['props'], 'name'), $play['props']);
                    isset($play['mats']) && $play['mats'] = collect($play['mats'])->groupBy('type')->toArray();
                    // true 留下
                    $play['filter'] = isset($play['mats']['ocr']) || isset($play['mats']['asr']);
                    return $play;
                })->filter(function ($play) {
                    return $play['filter'];
                });


            $items = [];
            collect($search_list)->each(function ($search) use (&$items) {
                $cover = $search['files']['cover']['url'];
                $item = [
                    'uuid' => $search['id'],
                    'title' => $search['title'],
                    'item_name' => $search['props']['prog']['label'],
                    'duration_str' => microSecToTimeStr($search['media']['duration']),
                    'publish_time' => date('Y-m-d H:i:s', $search['published']),
                    'cp_name' => $search['props']['chan']['label'],
                    'video_url' => $search['files']['video_hd']['url'] ?? $search['files']['video']['url'],
                ];

                // 遍历ocr结果
                isset($search['mats']['ocr']) && collect($search['mats']['ocr'])->each(function (
                    $ocr
                ) use (
                    &$items,
                    $item,
                    $cover
                ) {

                    if (isset($ocr['offsets']) && count($ocr['offsets']) > 4) { //兼容offsets不合格数据
                        $image = str_replace('cover', 'ocr-bin',
                                $cover) . '?' . $ocr['offsets'][3] . '_' . $ocr['offsets'][4] . '_0';
                        $item = array_merge($item, [
                            'time_start_str' => secToTimeStr($ocr['offsets'][1]),
                            'time_start' => $ocr['offsets'][1],
                            'image' => $image,
                            'brief' => [$ocr['text']],
                            'ai_type' => 'ocr',
                        ]);

                        $items[] = $item;
                    }

                });

                // 遍历asr结果
                isset($search['mats']['asr']) && collect($search['mats']['asr'])->each(function (
                    $asr
                ) use (
                    &$items,
                    $item,
                    $cover
                ) {
                    if (isset($asr['offsets']) && count($asr['offsets']) > 4) { //兼容offsets不合格数据
                        $image = str_replace('cover', 'ocr-bin',
                                $cover) . '?' . $asr['offsets'][3] . '_' . $asr['offsets'][4] . '_0';
                        $item = array_merge($item, [
                            'time_start_str' => secToTimeStr($asr['offsets'][1]),
                            'time_start' => $asr['offsets'][1],
                            'image' => $image,
                            'brief' => [$asr['text']],
                            'ai_type' => 'asr',
                        ]);

                        $items[] = $item;
                    }
                });


            });

            $page_info = [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'more' => $total > $page * $limit && $page * $limit < 500,
                //'data' => $data
            ];

            return $this->toJson(compact('items', 'page_info'));

        } else {
            return $this->toJson([
                'items' => [],
                'page_info' => [
                    'page' => $this->query('page') ?? 1,
                    'limit' => $this->query('limit') ?? 20,
                    'more' => false,
                    'total' => 0,
                ]
            ]);
        }


    }



    /**
     * @SWG\Get(
     *      path="/intelligent-creation/video-search-person",
     *      tags={"智能创作"},
     *      summary="人物搜索",
     *      security={
     *          {
     *              "Bearer":{}
     *          }
     *      },
     *      @SWG\Parameter(in="query",name="q",description="搜索关键字",required=true,type="string",),
     *      @SWG\Parameter(in="query",name="page",description="当前页码",required=false,type="integer",),
     *      @SWG\Parameter(in="query",name="limit",description="每页数据量"  ,required=false,type="integer",),
     *      @SWG\Response(
     *          response=200,
     *          description="请求成功",
     *          @SWG\Schema(
     *              type="object",
     *              @SWG\Property(property="code", type="string",description="状态码"),
     *              @SWG\Property(property="msg", type="string",description="提示信息"),
     *                  @SWG\Property(property="data", type="array",
     *                      @SWG\Items(type="object",
     *                          @SWG\Property(property="uuid", type="string",description="视频唯一标识码"),
     *                          @SWG\Property(property="title", type="string",description="标题"),
     *                          @SWG\Property(property="item_name", type="string",description="栏目名字"),
     *                          @SWG\Property(property="duration_str", type="string",description="视频时长"),
     *                          @SWG\Property(property="publish_time", type="string",description="播出时间"),
     *                          @SWG\Property(property="cp_name", type="string",description="频道名字"),
     *                          @SWG\Property(property="video_url", type="string",description="视频链接"),

     *                          @SWG\Property(property="time_start_str", type="string",description="开始时间str"),
     *                          @SWG\Property(property="time_start", type="string",description="开始时间（秒）"),
     *                          @SWG\Property(property="image", type="string",description="图片链接"),
     *                          @SWG\Property(property="person", type="string",description="人物名字"),
     *                          @SWG\Property(property="ai_type", type="string",description="ai类型 per"),
     *                      ),
     *                  ),
     *          )
     *      ),
     * )
     */


    public function getSearchPerson()
    {

        $this->rule([
                'q' => 'required',
            ]
        );
        $q = $this->query('q');
        $page = $this->query('page') ?? 1;
        $limit = $this->query('limit') ?? 20;

        if (in_array($this->query('q'), SensitiveWord::getCachedSensitiveWords())) {
            return $this->toJson([
                'items' => [],
                'page_info' => [
                    'page' => $this->query('page') ?? 1,
                    'limit' => $this->query('limit') ?? 20,
                    'more' => false,
                    'total' => 0,
                ]
            ]);
        }



        $params = [
            'q' => "@(text_per) {$q}",
            'sortby' => urlencode("published DESC"),
            'hit_start' => ($page - 1) * $limit,
            'hit_size' => $limit,
        ];

        $body = $this->getTVMSearchHttpQueryStr($params, []);
        $url = config('video.tvm_search.base_url') . '/tse/v1/doc/query?fields=files/name:ocr,video,video_hd||props/name:chan,prog||media/duration&field_match_size=10&mode=2&';// . $query_str;
        $data = $this->searchRequest($url, $body, 10);
        if ($data && $data['kind'] == 'DocList' && isset($data['status']['hit_total']) && isset($data['items'])) {
            $total = $data['status']['hit_total'];
            $search_list = collect($data['items'])
                ->map(function ($play) {
                    //兼容没有files的坑
                    if (!isset($play['files'])) {
                        $play['filter'] = false;
                        return $play;
                    }
                    $play['files'] = array_combine(array_column($play['files'], 'name'), $play['files']);
                    $play['props'] = array_combine(array_column($play['props'], 'name'), $play['props']);
                    isset($play['mats']) && $play['mats'] = collect($play['mats'])->groupBy('type')->toArray();
                    // true 留下
                    $play['filter'] = isset($play['mats']['per']);
                    return $play;
                })->filter(function ($play) {
                    return $play['filter'];
                });


            $items = [];
            collect($search_list)->each(function ($search) use (&$items) {
                $ocr_bin = $search['files']['ocr']['url'];
                $series = $search['files']['ocr']['series'];

                $item = [
                    'uuid' => $search['id'],
                    'title' => $search['title'],
                    'item_name' => $search['props']['prog']['label'],
                    'duration_str' => microSecToTimeStr($search['media']['duration']),
                    'publish_time' => date('Y-m-d H:i:s', $search['published']),
                    'cp_name' => $search['props']['chan']['label'],
                    'video_url' => $search['files']['video_hd']['url'] ?? $search['files']['video']['url'],
                ];


                // 遍历ocr结果
                isset($search['mats']['per']) && collect($search['mats']['per'])->each(function (
                    $per
                ) use (
                    &$items,
                    &$item,
                    &$ocr_bin,
                    &$series
                ) {
                    $second = $per['offsets'][1];
                    if (!isset($series[$second])) {
                        return true;
                    }
                    $image = $ocr_bin . '?' . $series[$second]['vars'];
                    $item = array_merge($item, [
                        'time_start_str' => secToTimeStr($second),
                        'time_start' => $second,
                        'image' => $image,
                        'person' => $per['text'],
                        'ai_type' => 'per',
                    ]);

                    $items[] = $item;
                });


            });

            $page_info = [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'more' => $total > $page * $limit && $page * $limit < 500,
            ];

            return $this->toJson(compact('items', 'page_info'));

        } else {
            return $this->toJson([
                'items' => [],
                'page_info' => [
                    'page' => $this->query('page') ?? 1,
                    'limit' => $this->query('limit') ?? 20,
                    'more' => false,
                    'total' => 0,
                ]
            ]);
        }
    }



    /**
     * @SWG\Get(
     *      path="/intelligent-creation/video-search-object",
     *      tags={"智能创作"},
     *      summary="人物搜索",
     *      security={
     *          {
     *              "Bearer":{}
     *          }
     *      },
     *      @SWG\Parameter(in="query",name="q",description="搜索关键字",required=true,type="string",),
     *      @SWG\Parameter(in="query",name="page",description="当前页码",required=false,type="integer",),
     *      @SWG\Parameter(in="query",name="limit",description="每页数据量"  ,required=false,type="integer",),
     *      @SWG\Response(
     *          response=200,
     *          description="请求成功",
     *          @SWG\Schema(
     *              type="object",
     *              @SWG\Property(property="code", type="string",description="状态码"),
     *              @SWG\Property(property="msg", type="string",description="提示信息"),
     *                  @SWG\Property(property="data", type="array",
     *                      @SWG\Items(type="object",
     *                          @SWG\Property(property="uuid", type="string",description="视频唯一标识码"),
     *                          @SWG\Property(property="title", type="string",description="标题"),
     *                          @SWG\Property(property="item_name", type="string",description="栏目名字"),
     *                          @SWG\Property(property="duration_str", type="string",description="视频时长"),
     *                          @SWG\Property(property="publish_time", type="string",description="播出时间"),
     *                          @SWG\Property(property="cp_name", type="string",description="频道名字"),
     *                          @SWG\Property(property="video_url", type="string",description="视频链接"),

     *                          @SWG\Property(property="time_start_str", type="string",description="开始时间str"),
     *                          @SWG\Property(property="time_start", type="string",description="开始时间（秒）"),
     *                          @SWG\Property(property="image", type="string",description="图片链接"),
     *                          @SWG\Property(property="brief", type="string",description="关键词"),
     *                          @SWG\Property(property="ai_type", type="string",description="ai类型 obj"),
     *                      ),
     *                  ),
     *          )
     *      ),
     * )
     */

    public function getSearchObject()
    {

        $this->rule([
                'q' => 'required',
            ]
        );
        $q = $this->query('q');
        $page = $this->query('page') ?? 1;
        $limit = $this->query('limit') ?? 20;


        if (in_array($this->query('q'), SensitiveWord::getCachedSensitiveWords())) {
            return $this->toJson([
                'items' => [],
                'page_info' => [
                    'page' => $this->query('page') ?? 1,
                    'limit' => $this->query('limit') ?? 20,
                    'more' => false,
                    'total' => 0,
                ]
            ]);
        }



        $params = [
            'q' => "@(text_obj) {$q}",
            'sortby' => urlencode("published DESC"),
            'hit_start' => ($page - 1) * $limit,
            'hit_size' => $limit,
        ];

        $body = $this->getTVMSearchHttpQueryStr($params, []);
        $url = config('video.tvm_search.base_url') . '/tse/v1/doc/query?fields=files/name:cover,video,video_hd||props/name:chan,prog||media/duration&field_match_size=10&mode=2&';// . $query_str;
        $data = $this->searchRequest($url, $body, 10);
        if ($data && $data['kind'] == 'DocList' && isset($data['status']['hit_total']) && isset($data['items'])) {
            $total = $data['status']['hit_total'];
            $search_list = collect($data['items'])
                ->map(function ($play) {
                    //兼容没有files的坑
                    if (!isset($play['files'])) {
                        $play['filter'] = false;
                        return $play;
                    }
                    $play['files'] = array_combine(array_column($play['files'], 'name'), $play['files']);
                    $play['props'] = array_combine(array_column($play['props'], 'name'), $play['props']);
                    isset($play['mats']) && $play['mats'] = collect($play['mats'])->groupBy('type')->toArray();
                    // true 留下
                    $play['filter'] = isset($play['mats']['obj']);
                    return $play;
                })->filter(function ($play) {
                    return $play['filter'];
                });

            $items = [];
            collect($search_list)->each(function ($search) use (&$items) {
                $cover = $search['files']['cover']['url'];
                $item = [
                    'uuid' => $search['id'],
                    'title' => $search['title'],
                    'item_name' => $search['props']['prog']['label'],
                    'duration_str' => microSecToTimeStr($search['media']['duration']),
                    'publish_time' => date('Y-m-d H:i:s', $search['published']),
                    'cp_name' => $search['props']['chan']['label'],
                    'video_url' => $search['files']['video_hd']['url'] ?? $search['files']['video']['url'],
                ];

                // 遍历ocr结果
                isset($search['mats']['obj']) && collect($search['mats']['obj'])->each(function (
                    $obj
                ) use (
                    &$items,
                    $item,
                    $cover
                ) {

                    if (isset($obj['offsets']) && count($obj['offsets']) > 4) { //兼容offsets不合格数据
                        $image = str_replace('cover', 'ocr-bin',
                                $cover) . '?' . $obj['offsets'][3] . '_' . $obj['offsets'][4] . '_0';
                        $item = array_merge($item, [
                            'time_start_str' => secToTimeStr($obj['offsets'][1]),
                            'time_start' => $obj['offsets'][1],
                            'image' => $image,
                            'brief' => [$obj['text']],
                            'ai_type' => 'obj',
                        ]);

                        $items[] = $item;
                    }

                });

            });

            $page_info = [
                'page' => $page,
                'limit' => $limit,
                'total' => $total,
                'more' => $total > $page * $limit && $page * $limit < 500,
            ];

            return $this->toJson(compact('items', 'page_info'));

        } else {
            return $this->toJson([
                'items' => [],
                'page_info' => [
                    'page' => $this->query('page') ?? 1,
                    'limit' => $this->query('limit') ?? 20,
                    'more' => false,
                    'total' => 0,
                ]
            ]);
        }
    }


}