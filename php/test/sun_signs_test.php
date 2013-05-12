<?php

/**
 * 星座処理のテストケース
 * 
 * @author written by にゃー (mirai_iro)
 * @author managed by まるあみゅ.ねっと (http://maruamyu.net/)
 */

/** 星座処理クラス群のロード */
include '../sun_signs.inc.php';

/**
 * 星座処理のテストケース
 */
class Maruamyu_Core_SunSigns_SunSignTest extends PHPUnit_Framework_TestCase
{
    /** インスタンスを作成できる */
    public function testNewInstance()
    {
        $sunSign = new Maruamyu_Core_SunSigns_SunSign (0,0,  0,0, '', '');
        $this->assertInstanceOf('Maruamyu_Core_SunSigns_SunSign', $sunSign);
    }

    /** 内部の情報が異常かどうか調べることができる */
    public function testIsValidWhenInvalid()
    {
        $invalidSunSign = new Maruamyu_Core_SunSigns_SunSign (0,0,  0,0, '', '');
        $this->assertFalse($invalidSunSign->isValid());
    }

    /** 内部の情報が正常かどうか調べることができる */
    public function testIsValidWhenValid()
    {
        $validSunSign = new Maruamyu_Core_SunSigns_SunSign (3,21, 4,20, '牡羊', 'おひつじ');
        $this->assertTrue($validSunSign->isValid());
    }

    /** 異常な場合は期間の表記が空文字で取得できる */
    public function testRangeLabelWhenInvalid()
    {
        $invalidSunSign = new Maruamyu_Core_SunSigns_SunSign (0,0,  0,0, '', '');
        $this->assertSame('', $invalidSunSign->rangeLabel());
    }

    /** 正常な場合は期間の表記が期間を表す文字列で取得できる */
    public function testRangeLabelWhenValid()
    {
        $invalidSunSign = new Maruamyu_Core_SunSigns_SunSign (4,21,  5,20, '牡牛', 'おうし');
        $this->assertSame('4/21 - 5/20', $invalidSunSign->rangeLabel());
    }

    /** 異常な場合は漢字表記が空文字で取得できる */
    public function testNameKanjiWhenInvalid()
    {
        $invalidSunSign = new Maruamyu_Core_SunSigns_SunSign (0,0,  0,0, '', '');
        $this->assertSame('', $invalidSunSign->nameKanji());
    }

    /** 正常な場合は漢字表記が漢字表記で取得できる */
    public function testNameKanjiWhenValid()
    {
        $validSunSign = new Maruamyu_Core_SunSigns_SunSign (5,21,  6,20, '双子', 'ふたご');
        $this->assertSame('双子座', $validSunSign->nameKanji());
    }

    /** 異常な場合はひらがな表記が取得できる */
    public function testNameHiraganaWhenInvalid()
    {
        $invalidSunSign = new Maruamyu_Core_SunSigns_SunSign (0,0,  0,0, '', '');
        $this->assertSame('', $invalidSunSign->nameHiragana());
    }

    /** 正常な場合はひらがな表記がひらがな表記で取得できる */
    public function testNameHiraganaWhenValid()
    {
        $validSunSign = new Maruamyu_Core_SunSigns_SunSign (6,21, 7,21, '蟹', 'かに');
        $this->assertSame('かに座', $validSunSign->nameHiragana());
    }

    /** 12星座の星座情報ファクトリーからインスタンスを作成できる */
    public function testFactoryCreateInstanceValidDate()
    {
        $sunSign = Maruamyu_Core_SunSigns_SunSignFactory::createSunSign(1, 1);
        $this->assertInstanceOf('Maruamyu_Core_SunSigns_SunSign', $sunSign);
        $this->assertTrue($sunSign->isValid());
    }

    /** 12星座の星座情報ファクトリーから日付に対応したインスタンスが作成できる */
    public function testFactoryCreateInstanceLeo()
    {
        $sunSign = Maruamyu_Core_SunSigns_SunSignFactory::createSunSign(8, 14);
        $this->assertInstanceOf('Maruamyu_Core_SunSigns_SunSign', $sunSign);
        $this->assertTrue($sunSign->isValid());
        $this->assertSame('獅子座', $sunSign->nameKanji());
    }

    /** 12星座の星座情報ファクトリーに正しくない日付を渡すとnullデータが作成できる */
    public function testFactoryCreateInstanceInvalidDate()
    {
        $sunSign = Maruamyu_Core_SunSigns_SunSignFactory::createSunSign(1, 32);
        $this->assertInstanceOf('Maruamyu_Core_SunSigns_SunSign', $sunSign);
        $this->assertFalse($sunSign->isValid());
    }
}
