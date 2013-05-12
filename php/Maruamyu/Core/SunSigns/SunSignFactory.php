<?php

/**
 * 星座処理 / 12星座の星座情報ファクトリークラス
 * 
 * @author written by にゃー (mirai_iro)
 * @author managed by まるあみゅ.ねっと (http://maruamyu.net/)
 */

/**
 * 12星座の星座情報ファクトリークラス
 * 
 * 期間はWikipedia日本語版の「サン・サイン」 rev20130324 記載のものを使用。
 * @link http://ja.wikipedia.org/wiki/%E3%82%B5%E3%83%B3%E3%83%BB%E3%82%B5%E3%82%A4%E3%83%B3
 */
class Maruamyu_Core_SunSigns_SunSignFactory implements Maruamyu_Core_SunSigns_SunSignFactoryInterface
{
    /** 星座情報の内部キャッシュ */
    private static $SUN_SIGN_CACHE = null;

    /** 内部キャッシュのキー: nullデータ */
    const KEY_INVALID = 'invalid';
    /** 内部キャッシュのキー: 牡羊座 */
    const KEY_ARIES = 'aries';
    /** 内部キャッシュのキー: 牡牛座 */
    const KEY_TAURUS = 'taurus';
    /** 内部キャッシュのキー: 双子座 */
    const KEY_GEMINI = 'gemini';
    /** 内部キャッシュのキー: 蟹座 */
    const KEY_CANCER = 'cancer';
    /** 内部キャッシュのキー: 獅子座 */
    const KEY_LEO = 'leo';
    /** 内部キャッシュのキー: 乙女座 */
    const KEY_VIRGO = 'virgo';
    /** 内部キャッシュのキー: 天秤座 */
    const KEY_LIBRA = 'libra';
    /** 内部キャッシュのキー: 蠍座 */
    const KEY_SCORPIO = 'scorpio';
    /** 内部キャッシュのキー: 射手座 */
    const KEY_SAGITTARIUS = 'sagittarius';
    /** 内部キャッシュのキー: 山羊座 */
    const KEY_CAPRICORN = 'capricorn';
    /** 内部キャッシュのキー: 水瓶座 */
    const KEY_AQUARIUS = 'aquarius';
    /** 内部キャッシュのキー: 魚座 */
    const KEY_PISCES = 'pisces';

    /**
     * 指定された月日の星座情報オブジェクトを返す
     * 
     * @param int $month 月
     * @param int $day 日
     * @return Maruamyu_Core_SunSigns_SunSign 星座オブジェクト
     */
    public static function createSunSign($month, $day)
    {
        if (is_null(self::$SUN_SIGN_CACHE)) {self::initCache();}
        $key = self::getCacheKey($month, $day);
        return self::$SUN_SIGN_CACHE[$key];
    }

    /**
     * 内部の星座情報キャッシュを初期化する
     */
    private static function initCache()
    {
        self::$SUN_SIGN_CACHE = array(
            self::KEY_INVALID     => new Maruamyu_Core_SunSigns_SunSign ( 0, 0,  0, 0, '',     ''        ),
            self::KEY_ARIES       => new Maruamyu_Core_SunSigns_SunSign ( 3,21,  4,20, '牡羊', 'おひつじ'),
            self::KEY_TAURUS      => new Maruamyu_Core_SunSigns_SunSign ( 4,21,  5,20, '牡牛', 'おうし'  ),
            self::KEY_GEMINI      => new Maruamyu_Core_SunSigns_SunSign ( 5,21,  6,20, '双子', 'ふたご'  ),
            self::KEY_CANCER      => new Maruamyu_Core_SunSigns_SunSign ( 6,21,  7,21, '蟹',   'かに'    ),
            self::KEY_LEO         => new Maruamyu_Core_SunSigns_SunSign ( 7,22,  8,22, '獅子', 'しし'    ),
            self::KEY_VIRGO       => new Maruamyu_Core_SunSigns_SunSign ( 8,23,  9,22, '乙女', 'おとめ'  ),
            self::KEY_LIBRA       => new Maruamyu_Core_SunSigns_SunSign ( 9,23, 10,22, '天秤', 'てんびん'),
            self::KEY_SCORPIO     => new Maruamyu_Core_SunSigns_SunSign (10,23, 11,20, '蠍',   'さそり'  ),
            self::KEY_SAGITTARIUS => new Maruamyu_Core_SunSigns_SunSign (11,21, 12,21, '射手', 'いて'    ),
            self::KEY_CAPRICORN   => new Maruamyu_Core_SunSigns_SunSign (12,22,  1,20, '山羊', 'やぎ'    ),
            self::KEY_AQUARIUS    => new Maruamyu_Core_SunSigns_SunSign ( 1,21,  2,19, '水瓶', 'みずがめ'),
            self::KEY_PISCES      => new Maruamyu_Core_SunSigns_SunSign ( 2,20,  3,20, '魚',   'うお'    ),
        );
    }

    /**
     * 指定された月日から内部の星座情報キャッシュのキーを求める
     * 
     * @param int $month 月
     * @param int $day 日
     * @return int $key キー
     */
    private static function getCacheKey($month, $day)
    {
        if (!checkdate($month, $day, 2000)) {return self::KEY_INVALID;} # nullデータ
        $monthday = $month * 100 + $day;

        if ($monthday <= 120) {
            return self::KEY_CAPRICORN;
        } elseif ($monthday <= 219) {
            return self::KEY_AQUARIUS;
        } elseif ($monthday <= 320) {
            return self::KEY_PISCES;
        } elseif ($monthday <= 420) {
            return self::KEY_ARIES;
        } elseif ($monthday <= 520) {
            return self::KEY_TAURUS;
        } elseif ($monthday <= 620) {
            return self::KEY_GEMINI;
        } elseif ($monthday <= 721) {
            return self::KEY_CANCER;
        } elseif ($monthday <= 822) {
            return self::KEY_LEO;
        } elseif ($monthday <= 922) {
            return self::KEY_VIRGO;
        } elseif ($monthday <= 1022) {
            return self::KEY_LIBRA;
        } elseif ($monthday <= 1121) {
            return self::KEY_SCORPIO;
        } elseif ($monthday <= 1221) {
            return self::KEY_SAGITTARIUS;
        } else {
            return self::KEY_CAPRICORN;
        }
    }
}

return true;
