<?php

/**
 * 星座処理 / 星座情報
 * 
 * @author written by にゃー (mirai_iro)
 * @author managed by まるあみゅ.ねっと (http://maruamyu.net/)
 */

/**
 * 星座情報
 */
class Maruamyu_Core_SunSigns_SunSign implements Maruamyu_Core_SunSigns_SunSignInterface
{
    /** 正しく初期化された場合trueが設定される */
    private $isValid = false;

    /** 開始月 */
    private $startMonth;

    /** 開始日 */
    private $startDay;

    /** 終了月 */
    private $endMonth;

    /** 終了日 */
    private $endDay;

    /** 漢字表記(座はつけない) */
    private $nameKanji;

    /** ひらがな表記(座はつけない) */
    private $nameHiragana;

    /**
     * コンストラクタ
     * 
     * @param int $startMonth 開始月
     * @param int $startDay 開始日
     * @param int $endMonth 終了月
     * @param int $endDay 終了日
     * @param string $nameKanji 漢字表記(座はつけない)
     * @param string $nameHiragana ひらがな表記(座はつけない)
     */
    public function __construct($startMonth, $startDay, $endMonth, $endDay, $nameKanji, $nameHiragana)
    {
        $startMonth = intval($startMonth, 10);
        $startDay = intval($startDay, 10);
        if (!checkdate($startMonth, $startDay, 2000)) {return false;}

        $endMonth = intval($endMonth, 10);
        $endDay = intval($endDay, 10);
        if (!checkdate($endMonth, $endDay, 2000)) {return false;}

        if (strlen($nameKanji) < 1) {return false;}
        if (strlen($nameHiragana) < 1) {return false;}

        $this->startMonth = $startMonth;
        $this->startDay = $startDay;
        $this->endMonth = $endMonth;
        $this->endDay = $endDay;
        $this->nameKanji = $nameKanji;
        $this->nameHiragana = $nameHiragana;
        $this->isValid = true;
    }

    /**
     * 内部の情報が正常かどうか調べる
     * @return boolean $isValid true:正常, false:異常
     */
    public function isValid()
    {
        return $this->isValid;
    }

    /**
     * 期間の表記を返す
     * @return string $rangeLabel 期間の表記 (例:3/21 - 4/20)
     */
    public function rangeLabel()
    {
        if (!$this->isValid()) {return '';}
        return sprintf("%d/%d - %d/%d", $this->startMonth, $this->startDay, $this->endMonth, $this->endDay);
    }

    /**
     * 漢字表記を返す
     * @return string $nameKanji 星座名(例:牡羊座)
     */
    public function nameKanji()
    {
        if (!$this->isValid()) {return '';}
        return $this->nameKanji . '座';
    }

    /**
     * ひらがな表記を返す
     * @return string $nameHiragana 星座名(例:おひつじ座)
     */
    public function nameHiragana()
    {
        if (!$this->isValid()) {return '';}
        return $this->nameHiragana . '座';
    }
}

return true;
