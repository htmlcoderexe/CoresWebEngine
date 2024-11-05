<?php

class DataFill
{

    public static function CreateRandomChar($upper)
    {
        $r=rand(0,25);
        $c=$r+0x41;
        if(!$upper)
        {
            $c = $c | 0x20;
        }
        return chr($c);
    }
    public static function CreateRandomString($len)
    {
        $s="";
        for($i=0;$i<$len;$i++)
        {
                $s.=DataFill::CreateRandomChar(false);
        }
        return $s;
    }
    public static function CreateRandomUser()
    {
        $username=DataFill::CreateRandomString(8);
        $password="swordfish"; //as always
        $nickname=DataFill::CreateRandomString(10);
        $email="notarealuser@test";
        User::Create($username,$password,$nickname,$email);
        $user=new User($username);
        return $user;
    }
}