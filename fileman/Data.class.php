<?php



/**
 * Пътя до директорията за файловете е общ за всички инсталирани приложения
 */
defIfNot('FILEMAN_UPLOADS_PATH', substr(EF_UPLOADS_PATH, 0, strrpos(EF_UPLOADS_PATH, '/')) . "/fileman");


/**
 * Клас 'fileman_Data' - Указател към данните за всеки файл
 *
 *
 * @category  vendors
 * @package   fileman
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class fileman_Data extends core_Manager {
    
    
    /**
     * Заглавие на модула
     */
    var $title = 'Данни';
    
    var $loadList = 'plg_Created,fileman_Wrapper,plg_RowTools';
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        
        // хеш на съдържанието на файла
        $this->FLD("md5", "varchar(32)", array('caption' => 'MD5'));
        
        // Дължина на файла в байтове 
        $this->FLD("fileLen", "fileman_FileSize", array('caption' => 'Дължина'));
        
        // Път до файла
        $this->FNC("path", "varchar(10)", array('caption' => 'Път'));
        
        // Връзки към файла
        $this->FLD("links", "int", 'caption=Връзки,notNull');
        
      
        $this->setDbUnique('fileLen,md5', 'DNA');
        
    }
    
    
    /**
     * Абсорбира данните от указания файл и
     * и връща ИД-то на съхранения файл
     */
    static function absorbFile($file, $create = TRUE)
    {
        $rec = new stdClass();
        $rec->fileLen = filesize($file);
        $rec->md5 = md5_file($file);
        
        $rec->id = static::fetchField("#fileLen = $rec->fileLen  AND #md5 = '{$rec->md5}'", 'id');
        
        if(!$rec->id && $create) {
            $path = self::getFilePath($rec);
            
            if(@copy($file, $path)) {
                $rec->links = 0;
                $status = static::save($rec);
            } else {
                error("Не може да бъде копиран файла", array($file, $dir));
            }
        }
        
        return $rec->id;
    }
    
    
    /**
     * Абсорбира данните от от входния стринг и
     * връща ИД-то на съхранения файл
     */
    static function absorbString($string, $create = TRUE)
    {
        $rec = new stdClass();
        $rec->fileLen = strlen($string);
        $rec->md5 = md5($string);
        
        $rec->id = static::fetchField("#fileLen = $rec->fileLen  AND #md5 = '{$rec->md5}'", 'id');
        
        if(!$rec->id && $create) {
            
            $path = self::getFilePath($rec);
            
            expect(FALSE !== @file_put_contents($path, $string));
            
            $rec->links = 0;
            $status = static::save($rec);
        }
        
        return $rec->id;
    }
    
    
    /**
     * Изчислява пътя към файла
     */
    static function on_CalcPath($mvc, $rec)
    {
        $rec->path = self::getFilePath($rec);
    }
    
    
    /**
     * Увеличава с 1 брояча, отчиташ броя на свързаните файлове
     */
    function increaseLinks($id)
    {
        $rec = $this->fetch($id);
        
        if($rec) {
            $rec->links++;
            $this->save($rec, 'links');
        }
    }
    
    
    /**
     * Намалява с 1 брояча, отчиташ броя на свързаните файлове
     */
    function decreaseLinks($id)
    {
        $rec = $this->fetch($id);
        
        if($rec) {
            $rec->links--;
            
            if($rec->links < 0) $rec->links = 0;
            $this->save($rec, 'links');
        }
    }
    
    
    /**
     * След начално установяване(настройка) установява папката за съхранение на файловете
     */
    static function on_AfterSetupMVC($mvc, &$res)
    {
        if(!is_dir(FILEMAN_UPLOADS_PATH)) {
            if(!mkdir(FILEMAN_UPLOADS_PATH, 0777, TRUE)) {
                $res .= '<li><font color=red>' . tr('Не може да се създаде директорията') . ' "' . FILEMAN_UPLOADS_PATH . '"</font>';
            } else {
                $res .= '<li>' . tr('Създадена е директорията') . ' <font color=green>"' . FILEMAN_UPLOADS_PATH . '"</font>';
            }
        }
    }


    /**
     * Връща размера на файла във вербален вид
     * 
     * @param numeric $id - id' то на файла
     * 
     * @return string $verbalSize - Вербалното представяне на файла
     */
    static function getFileSize($id)
    {
        // Размера в битове
        $sizeBytes = fileman_Data::fetchField($id, 'fileLen');
        
        // Инстанция на класа за определяне на размера
        $FileSize = cls::get('fileman_FileSize');
        
        // Вербалното представяне на файла
        $verbalSize = $FileSize->toVerbal($sizeBytes);
        
        return $verbalSize;
    }
    
    
    /**
     * Връща пътя до файла на съответния запис
     * 
     * @param mixed $rec - id' на файла или записа на файла
     * 
     * @return string $path - Пътя на файла
     */
    static function getFilePath($rec)
    {
        if (is_numeric($rec)) {
            $rec = self::fetch($rec);
        }
        
        $path = FILEMAN_UPLOADS_PATH . "/" . $rec->md5 . "_" . $rec->fileLen;
        
        return $path;
    }
}