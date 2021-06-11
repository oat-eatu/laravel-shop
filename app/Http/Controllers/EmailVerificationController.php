<?php

namespace App\Http\Controllers;

use App\Exceptions\InvalidRequestException;
use App\Models\User;
use App\Notifications\EmailVerificationNotification;
use Illuminate\Http\Request;
use \Cache;

class EmailVerificationController extends Controller
{
    public function verify(Request $request)
    {
        $email = $request->email;
        $token = $request->token;
        if (!$email || !$token) {
            throw  new InvalidRequestException('验证链接不正确');
        }

        if ($token != Cache::get('email_verification_' . $email)) {
            throw new InvalidRequestException('验证链接不正确或已过期');
        }

        if (!$user = User::where('email', $email)->first()) {
            throw new InvalidRequestException('用户不存在');
        }
        // 将指定的 key 从缓存中删除，由于已经完成了验证，这个缓存就没有必要继续保留。
        Cache::forget('email_verification_' . $email);

        $user->update(['email_verified' => true]);

        // 最后告知用户邮箱验证成功。
        return view('pages.success', ['msg' => '邮箱验证成功']);
    }

    //手动发送邮件
    public function send(Request $request)
    {
        $user = $request->user();
        if ($user->email_verified) {
            throw new InvalidRequestException('您已验证过邮箱了');
        }

        // 调用 notify() 方法用来发送我们定义好的通知类
        $user->notify(new EmailVerificationNotification());

        return view('pages.success', ['msg' => '邮件发送成功']);
    }
}
