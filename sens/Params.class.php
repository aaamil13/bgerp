<?php


/**
 * Мениджър за параметрите на сензорите
 *
 *
 * @category  bgerp
 * @package   sens
 *
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 *
 * @since     v 0.1
 */
class sens_Params extends core_Manager
{
    /**
     * Необходими мениджъри
     */
    public $loadList = 'plg_Created, plg_RowTools2, sens_Wrapper';
    
    
    /**
     * Заглавие
     */
    public $title = 'Параметри, поддържани от сензорите';
    
    
    /**
     * Права за писане
     */
    public $canWrite = 'ceo,sens, admin';
    
    
    /**
     * Права за запис
     */
    public $canRead = 'ceo,sens, admin';
    
    
    /**
     * Кой може да го разглежда?
     */
    public $canList = 'ceo,admin,sens';
    
    
    /**
     * Кой може да разглежда сингъла на документите?
     */
    public $canSingle = 'ceo,admin,sens';
    
    
    /**
     * Описание на модела
     */
    public function description()
    {
        $this->FLD('unit', 'varchar(16)', 'caption=Означение, mandatory');
        $this->FLD('param', 'varchar(255)', 'caption=Параметър, mandatory');
        $this->FLD('details', 'varchar(255)', 'caption=Детайли');
        
        $this->setDbUnique('unit', 'params');
    }
    
    
    /**
     * Добавяме означението за съответната мерна величина
     *
     * @param core_Mvc $mvc
     * @param stdClass $row
     * @param stdClass $rec
     */
    public static function on_AfterRecToVerbal($mvc, $row, $rec)
    {
        $row->details = "<div style='float: right;'>{$row->details}</div>";
    }
    
    
    /**
     * Връща id-то под което е заведена мерната величина
     *
     * @param $param
     */
    public static function getIdByUnit($param)
    {
        $query = self::getQuery();
        $query->where('#unit="' . $param . '"');
        
        $res = $query->fetch();
        
        return $res->id;
    }
    
    
    /**
     * Ако няма дефинирани параметри, дефинира такива при инсталиране
     *
     * @param core_Mvc $mvc
     * @param stdClass $res
     */
    public static function on_AfterSetupMvc($mvc, &$res)
    {
        // Импортираме данните от CSV файла.
        // Ако той не е променян - няма да се импортират повторно
        $cntObj = csv_Lib::importOnce($mvc, 'sens/data/Params.csv');
        
        // Записваме в лога вербалното представяне на резултата от импортирането
        $res .= $cntObj->html;
    }
}
