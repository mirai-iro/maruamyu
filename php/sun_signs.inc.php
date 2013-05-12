<?php
/**
 * 星座処理
 * 
 * 利用例:
 * $sunSign = Maruamyu_Core_SunSigns_SunSignFactory::createSunSign($month, $day);
 * echo $sunSign->nameKanji();
 * 
 * @author written by にゃー (mirai_iro)
 * @author managed by まるあみゅ.ねっと (http://maruamyu.net/)
 */

/** 星座情報インタフェース */
include 'Maruamyu/Core/SunSigns/SunSignInterface.php';

/** 星座情報 */
include 'Maruamyu/Core/SunSigns/SunSign.php';

/** 星座情報ファクトリークラス インタフェース */
include 'Maruamyu/Core/SunSigns/SunSignFactoryInterface.php';

/** 12星座の星座情報ファクトリークラス */
include 'Maruamyu/Core/SunSigns/SunSignFactory.php';

return true;
