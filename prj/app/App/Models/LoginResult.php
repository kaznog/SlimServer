<?php

namespace App\Models;

/**
 * ログイン結果.
 * ログインボーナスや着信メールもカプセル化することになるかもなので.
 */
class LoginResult
{
    public $sessionId;
    public $playerId;
    public $loginBonus;
    public $mailCount;
    public $socialBonus;
    public $LivingTown;

    public function __construct()
    {
        $this->sessionId = false;
        $this->playerId = false;
        $this->loginBonus = false;
        $this->mailCount = false;
        $this->socialBonus = false;
        $this->LivingTown = false;
    }
}
