<?php



/**
 * Клас 'doc_ContragentDataIntf' - Интерфейс за данните на адресанта
 *
 *
 * @category  bgerp
 * @package   doc
 * @author    Yusein Yuseinov <yyuseinov@gmail.com>
 * @copyright 2006 - 2012 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @title     Интерфейс за данните на адресанта
 */
class doc_ContragentDataIntf
{
    
    
    /**
     * Връща данните на получателя
     * return object
     *
     * $obj->company    - Името на компанията
     * $obj->companyId  - Id' то на компанията - key(mvc=crm_Companies)
     * $obj->country    - Името на държавата
     * $obj->countryId  - Id' то на
     * $obj->vatNo      - ДДС номер на компанията
     * $obj->uicId      - Национален номер на компанията
     * $obj->pCode      - код
     * $obj->place      -
     * $obj->email      - Имейл
     * $obj->tel        - Телефон
     * $obj->fax        - Факс
     * $obj->address    - Адрес
     * 
     * $obj->name       - Име на физическо лице
     * $obj->personId   - ИД на лице - key(mvc=crm_Persons)
     * $obj->pTel       - Персонален телефон
     * $obj->pMobile    - Мобилен
     * $obj->pFax       - Персонален
     * $obj->pAddress   - Персонален адрес
     * $obj->pEmail     - Персонален имейл
     * $obj->salutation - Обръщение
     * 
     * $obj->fullAdress - Конкатенирания пълен адрес
     */
    function getContragentData($id)
    {
        $obj = $this->class->getContragentData1($id);
        //$obj->fullAdress = $this->class->getFullAdress($id);
        
        return $obj;
    }
    
    
	/**
     * Връща пълния адрес на контрагента
     * @param int $id - ид на контрагент
     * @return param $adress - адреса
     */
    function getFullAdress($id)
    {
        return $this->class->getFullAdress($id);
    }
    
    
	/**
     * Връща дали на контрагента се начислява ДДС
     */
    function shouldChargeVat($id)
    {
        return $this->class->shouldChargeVat($id);
    }
}