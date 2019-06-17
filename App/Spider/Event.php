<?php

namespace App\Spider;

use EasySwoole\Utility\File;
use QL\QueryList;

/**
 * Created by PhpStorm.
 * User: tioncico
 * Date: 19-6-16
 * Time: 上午9:53
 */
class Event
{

    static function consume($data, $html)
    {
        //获得一个queryList对象，并且防止报错
        libxml_use_internal_errors(true);
        if ($data['type'] == 1) {
            //消费类型为1，则代表还不是下载图片，需要进行二次消费
            //查询下一页链接，用于继续爬取数据
            @$ql = QueryList::html($html);
            $nextLink = $ql->find('.pagenavi a:last')->attr('href');
            RedisLogic::addConsume([
                'type' => 1,//消费标识
                'url'  => $nextLink,
            ]);
            $img = $ql->find('.main-image img:first')->attr('src');
//            var_dump($img);
            RedisLogic::addConsume([
                'type' => 2,//消费标识
                'url'  => $img,
            ]);
        } elseif ($data['type'] == 2) {
            $urlInfo = parse_url($data['url']);
            $filePath = EASYSWOOLE_ROOT.'/Temp/'.$urlInfo['host'].$urlInfo['path'];
//            var_dump($filePath);
            File::createFile($filePath,$html);
//            var_dump($data['url']);
        }
    }

    static function produce($data, $html)
    {
        //获得一个queryList对象，并且防止报错
        libxml_use_internal_errors(true);
        @$ql = QueryList::html($html);

        //查询下一页链接，用于继续爬取数据
        $nextLink = $ql->find('.next.page-numbers')->attr('href');
        RedisLogic::addProduce($nextLink);
        //查询所有需要爬取的数据，用于爬取里面的图片
        $imgConsumeList = $ql->find('.postlist #pins li a')->attrs('href')->all();
        foreach ($imgConsumeList as $value) {
            RedisLogic::addConsume([
                'type' => 1,//消费标识
                'url'  => $value,
            ]);
        }
        return true;
    }
}