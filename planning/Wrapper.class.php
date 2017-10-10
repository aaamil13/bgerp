<?php



/**
 * Планиране - опаковка
 *
 *
 * @category  bgerp
 * @package   planning
 * @author    Milen Georgiev <milen@download.bg>
 * @copyright 2006 - 2014 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 */
class planning_Wrapper extends plg_ProtoWrapper
{
    
    
    /**
     * Описание на табовете
     */
    function description()
    {
    	$this->TAB('planning_DirectProductionNote', 'Протоколи->Производство', 'ceo,planning,store');
    	$this->TAB('planning_ConsumptionNotes', 'Протоколи->Влагане', 'ceo,planning,store');
    	$this->TAB('planning_ReturnNotes', 'Протоколи->Връщане', 'ceo,planning,store');
    	$this->TAB('planning_Jobs', 'Задания', 'ceo,planning,job');
    	$this->TAB('planning_Tasks', 'Операции->Списък', 'ceo,planning,taskWorker');
    	$this->TAB('planning_AssetResources', 'Оборудване', 'ceo,planning');
    	
        $this->title = 'Планиране';
    }
    
    
    /**
     * Дефолтен контролър
     */
    function act_getStartCtr()
    {
    	if(haveRole('ceo,planning,store')){
    		redirect(array('planning_DirectProductionNote', 'list'));
    	} elseif(haveRole('job')){
    		redirect(array('planning_Jobs', 'list'));
    	} else {
    		redirect(array('planning_Tasks', 'list'));
    	}
    }
}