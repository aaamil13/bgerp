<?php
/**
 * Хранилището за перманентни данни.
 * В бъдеще може да използва NOSQL база данни
 *
 * @category   bgERP 2.0
 * @package    permanent
 * @title:     Хранилище за данни
 * @author     Димитър Минеков <mitko@extrapack.com>
 * @copyright  2006-2011 Experta Ltd.
 * @license    GPL 2
 * @since      v 0.1
 */


/**
 * Какъв е максималния размер на некомпресираните данни в байтове
 */
//defIfNot('DATA_MAX_UNCOMPRESS', 10000);


/**
 * Хранилището за перманентни данни.
 *
 * В бъдеще може да използва NOSQL база данни
 *
 *
 * @category  vendors
 * @package   permanent
 * @author    Dimiter Minekov <mitko@extrapack.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Хранилище за данни
 */
class permanent_Data extends core_Manager {
    
    
    /**
     * Титла
     */
    var $title = "Хранилище за данни";
    
    
    /**
     * Права
     */
    var $canWrite = "no_one";
    
    
    /**
     * Описание на модела (таблицата)
     */
    function description()
    {
        $this->FLD("key", 'varchar(64)', 'caption=Ключ');
        $this->FLD("data", 'blob(100000)');
        $this->FLD('isCompressed', 'enum(yes,no)');
        $this->FLD('isSerialized', 'enum(yes,no)');
        
        $this->setDbUnique('key');
        
        $this->load("plg_Created");
    }
    
    
    /**
     * Записва данните за посочения ключ.
     * Данните могат да бъдат скалар или обект или масив.
     * Изисква се или посочения ключ да го няма или редът под този ключ да не е заключен от друг потребител.
     */
    static function write($key, $data)
    {
        $conf = core_Packs::getConfig('permanent');
        
        $rec = permanent_Data::fetch("#key = '{$key}'");
        
        $rec->key = $key;
        
        if (is_object($data) || is_array($data)) {
            $rec->data = serialize($data);
            $rec->isSerialized = 'yes';
        } else {
            $rec->isSerialized = 'no';
            $rec->data = $data;
        }
        
        if (strlen($rec->data) > $conf->DATA_MAX_UNCOMPRESS) {
            $rec->data = gzcompress($rec->data);
            $rec->isCompressed = 'yes';
        } else {
            $rec->isCompressed = 'no';
        }
        
        permanent_Data::save($rec);
        
        // Изтриваме заключването
        core_Locks::release($key);
        
        return TRUE;
    }
    
    
    /**
     * Връща данните за посочения ключ, като го заключва
     *
     * @param varchar $key
     */
    static function read($key)
    {
        $PermData = cls::get('permanent_Data');
        
        if (!core_Locks::get($key)) {
            $PermData->Log("Грешка при четене - заключен обект");
            exit (1);
        }
        
        $rec = permanent_Data::fetch("#key = '{$key}'");
        
        if (!$rec) return;
        
        $data = $rec->data;
        
        if ($rec->isCompressed == 'yes') {
            $data = gzuncompress($data);
        }
        
        if ($rec->isSerialized == 'yes') {
            $data = unserialize($data);
        }
        
        return $data;
    }
    
    
    /**
     * Изтрива данните за посочения ключ
     *
     * @param varchar $key
     */
    function remove($key)
    {
        permanent_Data::delete("#key = '{$key}'");
    }
}