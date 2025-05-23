<?php

namespace App;

class RandomInvitationCodeGenerator implements InvitationCodeGenerator
{
    public function generate()
    {
        $pool = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

        return substr(str_shuffle(str_repeat($pool, 32)), 0, 32);
    }
}
