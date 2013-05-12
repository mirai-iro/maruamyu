<?php
/**
 * 星座処理 / 星座情報インタフェース
 * 
 * @author written by にゃー (mirai_iro)
 * @author managed by まるあみゅ.ねっと (http://maruamyu.net/)
 */

/**
 * 星座情報インタフェース
 */
interface Maruamyu_Core_SunSigns_SunSignInterface
{
    /**
     * 内部の情報が正常かどうか調べる
     * @return boolean $isValid true:正常, false:異常
     */
    public function isValid();

    /**
     * 期間の表記を返す
     * @return string $rangeLabel 期間の表記 (例:3/21-4/20)
     */
    public function rangeLabel();

    /**
     * 漢字表記を返す
     * @return string $nameKanji 星座名(例:牡羊座)
     */
    public function nameKanji();

    /**
     * ひらがな表記を返す
     * @return string $nameHiragana 星座名(例:おひつじ座)
     */
    public function nameHiragana();
}

return true;
