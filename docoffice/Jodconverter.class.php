<?php


/**
 * Плъгин за конвертиране на офис документи с помощта на JodConverter
 *
 * @category  vendors
 * @package   docoffice
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class docoffice_Jodconverter extends core_Manager
{
    
    
    /**
     * 
     */
    var $interfaces = 'docoffice_ConverterIntf';
    
    
    /**
     * 
     */
    var $title = 'JodConverter';
    
    
    /**
     * Конвертиране на офис документи с помощта на Jodconverter
     * 
     * @param fileHandler $fileHnd - Манупулатора на файла, който ще се конвертира
     * @param string $toExt - Разширението, в което ще се конвертира
     * @param array $params - Други параметри
     * 				$params['callBack'] - Класа и функцията, която ще се извикат след приключване на конвертирането
     * 				$params['fileInfoId'] - id към bgerp_FileInfo
     * 				$params['asynch'] - Дали скрипта да се стартира асинхронно или не
     */
    static function convertDoc($fileHnd, $toExt, $params=array())
    {
        // Разширението да е в долния регистър
        $toExt = strtolower($toExt);
        
        // Стартираме или рестартираме офис пакета
        docoffice_Office::prepareOffice();
        
        // Вземаме конфигурационните данни
        $conf = core_Packs::getConfig('docoffice');
        
        // Вземаме целия път до jodconverter
        $jodPath = getFullPath('docoffice/jodconverter/' . $conf->OFFICE_JODCONVERTER_VERSION . '/lib/jodconverter-core.jar');
        
         // Инстанция на класа
        $Script = cls::get(fconv_Script);
        
        // Пътя до изходния файл
        $outFilePath = $Script->tempDir . fileman_Files::getFileNameWithoutExt($fileHnd) . '.' . $toExt;
        
        // Задаваме файловете и параметрите
        $Script->setFile('INPUTF', $fileHnd);
        $Script->setFile('OUTPUTF', $outFilePath);
        
        // Задаваме пътя, като параметър
        $Script->setParam('JODPATH', $jodPath, TRUE);
        
        // Портра на който е стартиран офис пакета
        $port = docoffice_Office::getOfficePort();
        
        // Задаваме пътя, като параметър
        $Script->setParam('PORT', $port, TRUE);
        
        // TODO Хубаво е да се използва -p (порта), но не работи коректно във версия 3.0 beta 4
        // @see http://code.google.com/p/jodconverter/issues/detail?id=108&colspec=ID%20Type%20Status%20Priority%20Version%20Target%20Owner%20Summary
//        $lineExecStr = "java -jar [#JODPATH#] -p [#PORT#] [#INPUTF#] [#OUTPUTF#]";
        // Добавяме към изпълнимия скрипт
        $lineExecStr = "java -jar [#JODPATH#] [#INPUTF#] [#OUTPUTF#]";
        // @todo
        
        $errFilePath = self::getErrLogFilePath($outFilePath);
        
        // Скрипта, който ще конвертира
        $Script->lineExec($lineExecStr, array('LANG' => 'en_US.UTF-8', 'HOME' => $Script->tempPath, 'errFilePath' => $errFilePath));
        
        // Функцията, която ще се извика след приключване на операцията
        $Script->callBack('docoffice_Jodconverter::afterConvertDoc');
        
        $params['errFilePath'] = $errFilePath;
        
        // Други необходими променливи
        $Script->params = serialize($params);
        $Script->outFilePath = $outFilePath;
        $Script->fh = $fileHnd;
        
        // Заключваме Jodconverter
        static::lockJodconverter(100, 60);
        
        // Заключваме офис пакета
        docoffice_Office::lockOffice(100, 60);
        
        // Увеличаваме броя на направените конвертирания с единица
        docoffice_Office::increaseConvertCount();

        // Стартираме скрипта синхронно
        $Script->run($params['asynch']);
    }
    
    
    /**
     * Получава управелението след приключване на конвертирането.
     * 
     * @param fconv_Script $script - Парамтри
     * 
     * @return boolean
     */
    static function afterConvertDoc($script)
    {
        // Отключва офис пакета
        docoffice_Office::unlockOffice();
        
        // Отключваме Jodconverter
        docoffice_Jodconverter::unlockJodconverter();
        
        // Десериализираме параметрите
        $params = unserialize($script->params);

        // Ако има callBack функция
        if ($params['callBack']) {
            
            // Разделяме класа от метода
            $funcArr = explode('::', $params['callBack']);
            
            // Обект на класа
            $object = cls::get($funcArr[0]);
            
            // Метода
            $method = $funcArr[1];
            
            // Извикваме callBack функцията и връщаме резултата
            $result = call_user_func_array(array($object, $method), array($script)); 
            
            return $result;
        }
    }

	
	
	/**
     * Заключваме JodConverter
     * 
     * @param int $maxDuration - Максималното време за което ще се опитаме да заключим
     * @param int $maxTray - Максималният брой опити, за заключване
     */
    static function lockJodconverter($maxDuration=50, $maxTray=30)
    {
        core_Locks::get('jodconverter', $maxDuration, $maxTray, FALSE);
    }
    
    
    /**
     * Отключваме JodConverter
     */
    static function unlockJodconverter()
    {
        core_Locks::release('jodconverter');
    }
}