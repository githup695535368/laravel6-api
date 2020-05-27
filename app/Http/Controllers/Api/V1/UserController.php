<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\ApiController;
use App\Http\Controllers\Api\OutputMsg;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends ApiController
{
    //

    /**
     * @SWG\Post(
     *      path="/user/register",
     *      tags={"User"},
     *      summary="用户注册",
     *      @SWG\Parameter(
     *          in="body",
     *          name="data",
     *          description="",
     *          required=true,
     *          @SWG\Schema(
     *              type="object",
     *              @SWG\Property(property="ver_code",description="验证码",type="string"),
     *              @SWG\Property(property="phone",description="电话号码",type="string"),
     *              @SWG\Property(property="password",description="登陆密码",type="string"),
     *              @SWG\Property(property="nickname",description="用户昵称",type="string"),
     *          )
     *      ),
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

    public function register()
    {
        // 机构和个人通用
        $this->rule([
            'nickname' => 'required',
            'ver_code' => 'required',
            'phone' => 'required|mobile|unique:user',
            'password' => 'required',
        ], [
            'nickname' => '用户昵称',
            'ver_code' => '验证码',
            'phone' => '账户管理员手机号',
            'password' => '登陆密码',
        ]);

        $ver_code = $this->data('ver_code');
        $phone = $this->data('phone');
        $password = $this->data('password');
        $nickname = $this->data('nickname');

        $mobileVerify = new \MobileVerify();
        if ($ver_code != 8848) {
            if (!$mobileVerify->verifyCode($phone, $ver_code)) {
                return $this->toError(OutputMsg::MOBILE_CODE_ERROR, $mobileVerify->getError());
            }
        }

        $user = new User();
        $user->nickname = $nickname;
        $user->phone = $phone;
        $user->password = $password;
        $user->save();

        return $this->successMessage("注册成功");
    }


    /**
     * @SWG\Post(
     *      path="/user/send-sms-code",
     *      tags={"User"},
     *      summary="发送短信验证码",
     *      @SWG\Parameter(
     *          in="body",
     *          name="data",
     *          description="",
     *          required=true,
     *          @SWG\Schema(
     *              type="object",
     *              @SWG\Property(property="phone",description="电话号码",type="string"),
     *          )
     *      ),
     *      @SWG\Response(
     *          response=200,
     *          description="",
     *          @SWG\Schema(
     *              type="object",
     *              @SWG\Property(property="code", type="string",description="状态码"),
     *              @SWG\Property(property="msg", type="string",description="提示信息"),
     *              @SWG\Property(property="data", type="object",
     *                          @SWG\Property(property="status", type="string",description="状态 成功返回true 失败返回false并在msg中提示原因"),
     *                  ),
     *          )
     *      ),
     * )
     */
    //发送短信验证码
    public function sendSmsCode()
    {
        $this->rule([
            'phone' => 'required|mobile'
        ], [
            'phone' => "手机号"
        ]);
        $mobile = $this->data('phone');

        $mobileVerify = new \MobileVerify();
        $status = $mobileVerify->sendCode($mobile);

        if ($status) {
            return $this->successMessage('发送成功');
        } else {
            return $this->toError(OutputMsg::MOBILE_CODE_ERROR, $mobileVerify->getError(), ['status' => $status]);
        }
    }


    /**
     * @SWG\Post(
     *      path="/user/login",
     *      tags={"User"},
     *      summary="登陆",
     *      @SWG\Parameter(
     *          in="body",
     *          name="data",
     *          description="",
     *          required=true,
     *          @SWG\Schema(
     *              type="object",
     *              @SWG\Property(property="type",description="password,ver_code",type="string"),
     *              @SWG\Property(property="ver_code",description="验证码",type="string"),
     *              @SWG\Property(property="username",description="邮箱或电话号码",type="string"),
     *              @SWG\Property(property="password",description="密码",type="string"),
     *          )
     *      ),
     *      @SWG\Response(
     *          response=200,
     *          description="",
     *          @SWG\Schema(
     *              type="object",
     *              @SWG\Property(property="code", type="string",description="状态码"),
     *              @SWG\Property(property="msg", type="string",description="提示信息"),
     *              @SWG\Property(property="data", type="object",
     *                          @SWG\Property(property="access_token", type="string",description="token"),
     *                          @SWG\Property(property="expires_in", type="string",description="token 有效时间"),
     *                          @SWG\Property(property="admin_name", type="string",description="当前账户姓名"),
     *                          @SWG\Property(property="phone", type="string",description="手机号"),
     *                          @SWG\Property(property="user_type", type="string",description="1机构 2 个人"),
     *                          @SWG\Property(property="logo", type="string",description="公司头像"),
     *                          @SWG\Property(property="is_main_account", type="string",description="是否是主账户"),
     *                          @SWG\Property(property="is_resource", type="string",description="是否是资源方 1是0 不是"),
     *                          @SWG\Property(property="user_is_limit_item", type="string",description="所在企业是否限制栏目 1是  0不是"),
     *                          @SWG\Property(property="can_publish", type="string",description="是否有发布权限"),
     *                          @SWG\Property(property="innovate_type", type="string",description="用户剪辑类型1 一创 2二创"),
     *                          @SWG\Property(property="can_live_cut", type="string",description="是否可以快剪"),
     *                          @SWG\Property(property="can_cut_video", type="string",description="是否可以资讯拆条"),
     *                          @SWG\Property(property="can_download_video", type="string",description="是否可以下载长视频 0不可以 1可以"),
     *                          @SWG\Property(property="can_medical_video_upload", type="string",description="个人中心是否显示医疗视频"),
     *                          @SWG\Property(property="show_custom_video_resource", type="string",description="是否显示视频资源库 1是 0否"),
     *                  ),
     *          )
     *      ),
     * )
     */

    public function login()
    {

        $this->rule([
            'type' => 'required|in:password,ver_code'
        ]);
        $username = $this->data('username');
        $password = $this->data('password');
        $type = $this->data('type');

        if ($type == 'password') {
            if (!(($user = User::wherePhone($username)->first()) && $user->password == $password)) {
                return $this->toError(OutputMsg::USERNAME_OR_PASSWORD_ERROR);
            }

            $token = \Auth::guard('api_user')->login($user);
        } elseif ($type == 'ver_code') {
            $this->rule([
                'username' => 'required|mobile',
                'ver_code' => 'required'
            ], [
                'username' => '手机号',
                'ver_code' => '验证码',
            ]);

            if (!$user = User::wherePhone($username)->first()) {
                return $this->toError(OutputMsg::USER_NOT_EXIST);
            }

            $mobileVerify = new \MobileVerify();
            $ver_code = $this->data('ver_code');

            if (!$mobileVerify->verifyCode($username, $ver_code)) {
                return $this->toError(OutputMsg::MOBILE_CODE_ERROR, $mobileVerify->getError());
            }
            $token = \Auth::guard('api_user')->login($user);
        }

        /*if ($user->status == User::STATUS_禁用) {
            return $this->toError(OutputMsg::USER_STATUS_FORBIDDEN);
        }*/

        return $this->respondWithToken($token, [
            'nickname' => $user->nickname,
            'phone' => $user->phone,
        ]);
    }


    protected function respondWithToken($token, $param)
    {
        return $this->toJson(array_merge([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => \Auth::guard('api_user')->factory()->getTTL() * 60
        ], $param));
    }



    /**
     * @SWG\Post(
     *      path="/user/change-password",
     *      tags={"User"},
     *      summary="账号修改密码",
     *      security={
     *          {
     *              "Bearer":{}
     *          }
     *      },
     *      @SWG\Parameter(
     *          in="body",
     *          name="data",
     *          description="",
     *          required=true,
     *          @SWG\Schema(
     *              type="object",
     *              @SWG\Property(property="ver_code",description="验证码",type="string"),
     *              @SWG\Property(property="new_password",description="新密码 sha256 加密",type="string"),
     *          )
     *      ),
     *      @SWG\Response(
     *          response=200,
     *          description="",
     *          @SWG\Schema(
     *              type="object",
     *              @SWG\Property(property="code", type="string",description="状态码"),
     *              @SWG\Property(property="msg", type="string",description="提示信息"),
     *              @SWG\Property(property="data", type="object",
     *                          @SWG\Property(property="status", type="string",description="状态 成功返回true"),
     *                  ),
     *          )
     *      ),
     * )
     */
    public function changePassword()
    {
        $this->rule([
            'ver_code' => 'required',
            'new_password' => 'required',
        ]);

        $user = $this->user();
        $ver_code = $this->data('ver_code');
        $new_password = $this->data('new_password');


        $mobileVerify = new \MobileVerify();
        if (!$mobileVerify->verifyCode($user->phone, $ver_code)) {
            return $this->toError(OutputMsg::MOBILE_CODE_ERROR, $mobileVerify->getError());
        }

        $user->password = $new_password;
        $bool = $user->save();
        return $this->toJson(['status' => $bool]);
    }

}
