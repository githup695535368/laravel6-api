<?php
/**
 * Created by PhpStorm.
 * User: linzijie
 * Date: 2019-08-27
 * Time: 14:15
 */

namespace App\Http\Controllers\Api\V1;


use App\Http\Controllers\Api\ApiController;
use App\Logics\BaiduOpenPlatfrom\NLP\BaiduNLP;

class IntelligentCreationController extends ApiController
{


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

    public function AnalysisBaiJiaHaoArticleByUrl()
    {
        $this->rule([
            'url' => 'required',
        ]);

        $article_url = $this->query('url');
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


        $origin_text = implode('',$text_list);

        $nlp = new BaiduNLP();
        $origin_news_summary = $nlp->newsSummary($title, $origin_text, 360);
        $origin_lexer = $nlp->getLexer($origin_text);

        $filter_lexer = [];
        collect($origin_lexer['items'])->each(function ($item) use(&$filter_lexer) {
            if(in_array($item['ne'], ['PER','LOC','ORG']) || in_array($item['pos'],['nr','ns','nt','nw','nz'])){
                if(!array_key_exists($item['item'], $filter_lexer)){
                    $filter_lexer[$item['item']] = $item;
                }
            }
        });


        return $this->toJson([
            'news_summary' => $origin_news_summary['summary'],
            'image_list' => $image_list,
            'lexer' => array_values($filter_lexer),
            'article_url' => $article_url
        ]);
    }


}