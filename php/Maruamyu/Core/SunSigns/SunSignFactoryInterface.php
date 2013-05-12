<?php

/**
 * 星座処理 / 星座情報ファクトリークラス インタフェース
 * 
 * @author written by にゃー (mirai_iro)
 * @author managed by まるあみゅ.ねっと (http://maruamyu.net/)
 */

/**
 * 星座情報ファクトリークラス インタフェース
 */
interface Maruamyu_Core_SunSigns_SunSignFactoryInterface
{
    /**
     * 指定された月日の星座情報オブジェクトを返す
     * 
     * @param int $month 月
     * @param int $day 日
     * @return Maruamyu_Core_SunSigns_SunSignInterface 星座オブジェクト
     */
    public static function createSunSign($month, $day);
}

return true;
