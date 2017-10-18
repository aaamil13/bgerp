<?php

/**
 *  Клас  'unit_MinkPSales' - PHP тестове за проверка на продажби различни случаи, вкл. некоректни данни
 *
 * @category  bgerp
 * @package   tests
 * @author    Pavlinka Dainovska <pdainovska@gmail.com>
 * @copyright 2006 - 2017 Experta OOD
 * @license   GPL 3
 * @since     v 0.1
 * @link
 */

class unit_MinkPSales extends core_Manager {
    //Изпълнява се след unit_MinkPbgERP!
    //http://localhost/unit_MinkPSales/Run/
    public function act_Run()
    {
        if (!TEST_MODE) {
            return;
        }
        
        $res = '';
        $res .= '<br>'.'MinkPSales';
        $res .= "  1.".$this->act_SaleQuantityMinus();
        $res .= "  2.".$this->act_SaleQuantityZero();
        $res .= "  3.".$this->act_CreateSaleInvalidData();
        $res .= "  4.".$this->act_CreateSaleInvalidData1();
        $res .= "  5.".$this->act_SalePriceMinus();
        $res .= "  6.".$this->act_SaleDiscountMinus();
        $res .= "  7.".$this->act_SaleDiscount101();
        $res .= "  8.".$this->act_CreateSaleControlInvoiceDate();
        $res .= "  9.".$this->act_CreateSaleVatInclude();
        $res .= "  10.".$this->act_CreateSaleEURVatFree3();
        $res .= "  11.".$this->act_CreateSaleEURVatFreeAdv();
        $res .= "  12.".$this->act_CreateCreditDebitInvoice();
        $res .= "  13.".$this->act_CreateCreditDebitInvoiceVATFree();
        $res .= "  14.".$this->act_CreateCreditDebitInvoiceVATNo();
        $res .= "  15.".$this->act_CreateCreditDebitInvoiceVATYes();
        //$res .= "  15.".$this->act_CreateCreditInvoiceDiffVATYes();
        $res .= "  16.".$this->act_CreateCreditInvoiceAdvPayment();
        $res .= "  17.".$this->act_CreateSaleAdvPaymentInclVAT();
        $res .= "  18.".$this->act_CreateSaleAdvPaymentSep();
        $res .= "  19.".$this->act_CreateSaleDifVAT();
        $res .= "  20.".$this->act_CreateSaleExtraIncome();
        $res .= "  21.".$this->act_CreateSaleAdvExtraIncome();
        $res .= "  22.".$this->act_CreateSaleAdvExtraIncome1();
        $res .= "  23.".$this->act_CreateSaleExtraExpenses();
        $res .= "  24.".$this->act_CreateSaleAdvExtraExpenses();
        $res .= "  25.".$this->act_CreateSaleAdvExtraExpenses1();
        $res .= "  26.".$this->act_CreateSaleManuf();
        $res .= "  27.".$this->act_CreateSaleService();
        $res .= "  28.".$this->act_CreateCreditInvoice();
       
        return $res;
    }
       
    /**
     * Логване
     */
    public function SetUp()
    {
        $browser = cls::get('unit_Browser');
        //$browser->start('http://localhost/');
        $host = unit_Setup::get('DEFAULT_HOST');
        $browser->start($host);
        //Потребител DEFAULT_USER (bgerp)
        $browser->click('Вход');
        $browser->setValue('nick', unit_Setup::get('DEFAULT_USER'));
        $browser->setValue('pass', unit_Setup::get('DEFAULT_USER_PASS'));
        $browser->press('Вход');
        return $browser;
    }
    
    /**
     * Избор на фирма
     */
    public function SetFirm()
    {
        $browser = $this->SetUp();
        $browser->click('Визитник');
        $browser->click('F');
        $Company = 'Фирма bgErp';
        $browser->click($Company);
        $browser->press('Папка');
        return $browser;
    }
    
    /**
     * Избор на чуждестранна фирма
     */
    public function SetFirmEUR()
    {
        $browser = $this->SetUp();
        $browser->click('Визитник');
        $browser->click('N');
        $Company = 'NEW INTERNATIONAL GMBH';
        $browser->click($Company);
        $browser->press('Папка');
        return $browser;
    }
    
    /**
     * 1. Проверка за отрицателно количество
     */
    //http://localhost/unit_MinkPSales/SaleQuantityMinus/
    function act_SaleQuantityMinus()
    {
      
        // Логваме се
        $browser = $this->SetUp();
        //Отваряме папката на фирмата
        $browser = $this->SetFirm();
        
        // нова продажба - проверка има ли бутон
        if(strpos($browser->gettext(), 'Продажба')) {
            $browser->press('Продажба');
        } else {
            $browser->press('Нов...');
            $browser->press('Продажба');
        }
    
        $browser->setValue('reff', 'QuantityMinus');
        $browser->setValue('note', 'MinkPSaleQuantityMinus');
        $browser->setValue('paymentMethodId', "В брой при получаване");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
        // Записваме черновата на продажбата
        $browser->press('Чернова');
        // Добавяме артикул
        $browser->press('Артикул');
        $browser->setValue('productId', 'Други продукти');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '-2');
        $browser->setValue('packPrice', '3');
        // Записваме артикула
        $browser->press('Запис');
        if(strpos($browser->gettext(), 'Некоректна стойност на полето \'Количество\'!')) {
        } else {
            return unit_MinkPbgERP::reportErr('Не дава грешка при отрицателно количество', 'warning');
        }
        
        if(strpos($browser->gettext(), 'Не е над - \'0,0000\'')) {
        } else {
            return unit_MinkPbgERP::reportErr('Не дава грешка при отрицателно количество', 'warning');
        }
        
    }
    
    /**
     * 2. Проверка за нулево количество
     */
    //http://localhost/unit_MinkPSales/SaleQuantityZero/
    function act_SaleQuantityZero()
    {
    
        // Логваме се
        $browser = $this->SetUp();
         
        //Отваряме папката на фирмата
         $browser = $this->SetFirm();
    
        // нова продажба - проверка има ли бутон
        if(strpos($browser->gettext(), 'Продажба')) {
            $browser->press('Продажба');
        } else {
            $browser->press('Нов...');
            $browser->press('Продажба');
        }
    
        $browser->setValue('reff', 'QuantityMinus');
        $browser->setValue('note', 'MinkPSaleQuantityZero');
        $browser->setValue('paymentMethodId', "В брой при получаване");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
        // Записваме черновата на продажбата
        $browser->press('Чернова');
        // Добавяме артикул
        $browser->press('Артикул');
        $browser->setValue('productId', 'Други продукти');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '0');
        $browser->setValue('packPrice', '3');
        // Записваме артикула
        $browser->press('Запис');
        if(strpos($browser->gettext(), 'Некоректна стойност на полето \'Количество\'!')) {
        } else {
            return unit_MinkPbgERP::reportErr('Не дава грешка при нулево количество', 'warning');
        }
    
        if(strpos($browser->gettext(), 'Не е над - \'0,0000\'')) {
        } else {
            return unit_MinkPbgERP::reportErr('Не дава грешка при нулево количество', 'warning');
        }
      
    }
    
    /**
     * 3. Проверка за некоректни данни в цена/количество - ,,100-3   200;-4    *.100-5  12.,5
     */
    //http://localhost/unit_MinkPSales/CreateSaleInvalidData/
    function act_CreateSaleInvalidData()
    {
    
        // Логваме се
        $browser = $this->SetUp();
        //Отваряме папката на фирмата
        $browser = $this->SetFirm();
         
        // нова продажба - проверка има ли бутон
        if(strpos($browser->gettext(), 'Продажба')) {
            $browser->press('Продажба');
        } else {
            $browser->press('Нов...');
            $browser->press('Продажба');
        }
    
        $browser->setValue('reff', 'InvalidData');
        $browser->setValue('note', 'MinkPSaleInvalidData');
        $browser->setValue('paymentMethodId', "В брой при получаване");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
        // Записваме черновата на продажбата
        $browser->press('Чернова');
        // Добавяме артикул
        $browser->press('Артикул');
        $browser->setValue('productId', 'Други продукти');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', ',,100-3');
        $browser->setValue('packPrice', '200;-4');
        $browser->setValue('discount', '*.100-5');
        // Записваме артикула
        $browser->press('Запис');
        if(strpos($browser->gettext(), 'Некоректна стойност на полето \'Количество\'!')) {
        } else {
            return unit_MinkPbgERP::reportErr("Не дава грешка при некоректна стойност на полето 'количество'", 'warning');
        }
        if(strpos($browser->gettext(), 'Некоректна стойност на полето \'Цена\'!')) {
        } else {
            return unit_MinkPbgERP::reportErr("Не дава грешка при некоректна стойност на полето 'Цена'", 'warning');
        }
        if(strpos($browser->gettext(), 'Некоректна стойност на полето \'Отстъпка\'!')) {
        } else {
            return unit_MinkPbgERP::reportErr("Не дава грешка при некоректна стойност на полето 'Отстъпка'", 'warning');
        }
        if(strpos($browser->gettext(), 'Грешка при превръщане на \',,100-3\' в число')) {
        } else {
            return unit_MinkPbgERP::reportErr("Не дава грешка при превръщане на \',,100-3\' в число", 'warning');
        }
        if(strpos($browser->gettext(), 'Недопустими символи в число/израз')) {
        } else {
            return unit_MinkPbgERP::reportErr('Не дава грешка при недопустими символи в число/изра', 'warning');
        }
        if(strpos($browser->gettext(), 'Грешка при превръщане на \'*.100-5\' в число')) {
        } else {
            return unit_MinkPbgERP::reportErr('Не дава грешка при превръщане на \'*.100-5\' в число', 'warning');
        }
         
    }
    
    /**
     * 4. Проверка 1 за некоректни данни в цена/количество - ((, (-
     */
    //http://localhost/unit_MinkPSales/CreateSaleInvalidData1/
    function act_CreateSaleInvalidData1()
    {
    
        // Логваме се
        $browser = $this->SetUp();
        //Отваряме папката на фирмата
        $browser = $this->SetFirm();
    
        // нова продажба - проверка има ли бутон
        if(strpos($browser->gettext(), 'Продажба')) {
            $browser->press('Продажба');
        } else {
            $browser->press('Нов...');
            $browser->press('Продажба');
        }
    
        $browser->setValue('reff', 'QuantityMinus');
        $browser->setValue('note', 'MinkPSaleInvalidData1');
        $browser->setValue('paymentMethodId', "В брой при получаване");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
        // Записваме черновата на продажбата
        $browser->press('Чернова');
        // Добавяме артикул
        $browser->press('Артикул');
        $browser->setValue('productId', 'Други продукти');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '2((3');
        $browser->setValue('packPrice', '3(-1');
        // Записваме артикула
        $browser->press('Запис');
        if(strpos($browser->gettext(), 'Некоректна стойност на полето \'Количество\'!')) {
        } else {
            return unit_MinkPbgERP::reportErr('Не дава грешка при невалидни данни', 'warning');
        }
        
        if(strpos($browser->gettext(), 'Некоректна стойност на полето \'Цена\'!')) {
        } else {
            return unit_MinkPbgERP::reportErr('Не дава грешка при невалидни данни', 'warning');
        }
     
    }
    
    
    /**
     * 5. Проверка за отрицателна цена
     */
    //http://localhost/unit_MinkPSales/SalePriceMinus/
    function act_SalePriceMinus()
    {
    
        // Логваме се
        $browser = $this->SetUp();
         
        //Отваряме папката на фирмата
         $browser = $this->SetFirm();
    
        // нова продажба - проверка има ли бутон
        if(strpos($browser->gettext(), 'Продажба')) {
            $browser->press('Продажба');
        } else {
            $browser->press('Нов...');
            $browser->press('Продажба');
        }
    
        $browser->setValue('reff', 'PriceMinus');
        $browser->setValue('note', 'MinkPSalePriceMinus');
        $browser->setValue('paymentMethodId', "В брой при получаване");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
        // Записваме черновата на продажбата
        $browser->press('Чернова');
        // Добавяме артикул
        $browser->press('Артикул');
        $browser->setValue('productId', 'Други продукти');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '2');
        $browser->setValue('packPrice', '-3');
        // Записваме артикула
        $browser->press('Запис');
        if(strpos($browser->gettext(), 'Сумата на реда не може да бъде под 0.01! Моля променете количеството и/или цената')) {
        } else {
            return unit_MinkPbgERP::reportErr('Не дава грешка при отрицателна цена', 'warning');
        }
    
    }
    
    /**
     * 6. Проверка за отрицателна отстъпка
     */
    //http://localhost/unit_MinkPSales/SaleDiscountMinus/
    function act_SaleDiscountMinus()
    {
    
        // Логваме се
        $browser = $this->SetUp();
       
        //Отваряме папката на фирмата
         $browser = $this->SetFirm();
    
        // нова продажба - проверка има ли бутон
        if(strpos($browser->gettext(), 'Продажба')) {
            $browser->press('Продажба');
        } else {
            $browser->press('Нов...');
            $browser->press('Продажба');
        }
        
        $browser->setValue('reff', 'DiscountMinus');
        $browser->setValue('note', 'MinkPSaleDiscountMinus');
        $browser->setValue('paymentMethodId', "В брой при получаване");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
        // Записваме черновата на продажбата
        $browser->press('Чернова');
        // Добавяме артикул
        $browser->press('Артикул');
        $browser->setValue('productId', 'Други продукти');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '2');
        $browser->setValue('packPrice', '2');
        $browser->setValue('discount', -3);
        // Записваме артикула
        $browser->press('Запис');
       
        if(strpos($browser->gettext(), 'Некоректна стойност на полето \'Отстъпка\'!')) {
        } else {
            return unit_MinkPbgERP::reportErr('Не дава грешка при отрицателна отстъпка', 'warning');
        }
         
        //if(strpos($browser->gettext(), 'Не е над - \'0,00 %\'')) {//не го разпознава
        //} else {
        //    return unit_MinkPbgERP::reportErr('Не дава грешка при отрицателна отстъпка', 'warning');
        //}
        //return $browser->getHtml();
    }
    
    /**
     * 7. Проверка за отстъпка, по-голяма от 100%
     */
    //http://localhost/unit_MinkPSales/SaleDiscount101/
    function act_SaleDiscount101()
    {
    
        // Логваме се
        $browser = $this->SetUp();
         
        //Отваряме папката на фирмата
         $browser = $this->SetFirm();
    
        // нова продажба - проверка има ли бутон
        if(strpos($browser->gettext(), 'Продажба')) {
            $browser->press('Продажба');
        } else {
            $browser->press('Нов...');
            $browser->press('Продажба');
        }
    
        $browser->setValue('reff', 'DiscountMinus');
        $browser->setValue('note', 'MinkPSaleDiscount101');
        $browser->setValue('paymentMethodId', "В брой при получаване");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
        // Записваме черновата на продажбата
        $browser->press('Чернова');
        // Добавяме артикул
        $browser->press('Артикул');
        $browser->setValue('productId', 'Други продукти');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '2');
        $browser->setValue('packPrice', '2');
        $browser->setValue('discount', '101,55');
        // Записваме артикула
        $browser->press('Запис');
         
        if(strpos($browser->gettext(), 'Некоректна стойност на полето \'Отстъпка\'!')) {
        } else {
            return unit_MinkPbgERP::reportErr('Не дава грешка при отстъпка над 100%', 'warning');
        }
        
        //if(strpos($browser->gettext(), 'Над допустимото - \'100,00 %\'')) {//не го разпознава
        //} else {
        //    return unit_MinkPbgERP::reportErr('Не дава грешка 1 при отстъпка над 100%', 'warning');
        //}
       
    } 
    
    /**
     * 8. Контрол на датата на фактурата - Sal11
     */
     
    //http://localhost/unit_MinkPSales/CreateSaleControlInvoiceDate/
    function act_CreateSaleControlInvoiceDate()
    {
    
        // Логване
        $browser = $this->SetUp();
    
        //Отваряне папката на фирмата
        $browser = $this->SetFirm();
    
        // нова продажба - проверка има ли бутон
        if(strpos($browser->gettext(), 'Продажба')) {
            $browser->press('Продажба');
        } else {
            $browser->press('Нов...');
            $browser->press('Продажба');
        }
         
        //$browser->hasText('Създаване на продажба');
        $browser->setValue('reff', 'MinkP');
        $browser->setValue('shipmentStoreId', '1');
        $browser->setValue('bankAccountId', '');
        $browser->setValue('note', 'MinkPSaleControlInvoiceDate');
        $browser->setValue('paymentMethodId', "До 3 дни след фактуриране");
        $browser->setValue('chargeVat', "Включено ДДС в цените");
        // Записване черновата на продажбата
        $browser->press('Чернова');
    
        // Добавяне на артикул
        $browser->press('Артикул');
        $browser->setValue('productId', 'Други стоки');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '1000');
        $browser->setValue('packPrice', '1');
        $browser->setValue('discount', 2);
    
        // Записване на артикула
        $browser->press('Запис');
    
        // активиране на продажбата
        $browser->press('Активиране');
        $browser->press('Активиране/Контиране');
         
        // Когато няма автом. избиране - ЕН
    
        // Фактура с днешна дата
        $browser->press('Фактура');
        $browser->setValue('additionalInfo', 'за оттегляне и контрол след възстановяване');
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        // Фактура с вчерашна дата - контрол, грешка и отказ
        $browser->press('Фактура');
        $dateInv=strtotime("-1 Day");
        $browser->setValue('date', date('d-m-Y', $dateInv));
        $browser->setValue('dueDate', '');
        $browser->press('Чернова');
    
        if(strpos($browser->gettext(), 'Не може да се запише фактура с дата по-малка от последната активна фактура в диапазона')) {
        } else {
            return unit_MinkPbgERP::reportErr('Не излиза съобщение за грешка - фактура със стара дата', 'warning');
        }
        $browser->press('Отказ');
        
        // Оттегляне на фактурата с днешна дата
        $browser->press('btnDelete53');
        
        // Фактура с вчерашна дата - контиране
        $browser->press('Фактура');
        $dateInv=strtotime("-1 Day");
        $browser->setValue('date', date('d-m-Y', $dateInv));
        $browser->setValue('dueDate', '');
        $browser->press('Чернова');
        $browser->press('Контиране');
        
        // Възстановяване на фактурата с днешна дата
        $browser->click('Показване на целия документ');
        $browser->press('Възстановяване');
        //$browser->press(btnRestore53);
        //Тук трябва да даде грешка "Не може да се възстанови фактура с по-малък номер и по-късна дата от съществуващата"
        //return $browser->getHtml();
        
        }
    
    /**
     * 9. Продажба - включено ДДС в цените, клониране Sal12
     */
     
    //http://localhost/unit_MinkPSales/CreateSaleVatInclude/
    function act_CreateSaleVatInclude()
    {
    
        // Логване
        $browser = $this->SetUp();
    
        //Отваряне папката на фирмата
         $browser = $this->SetFirm();
    
        // нова продажба - проверка има ли бутон
        if(strpos($browser->gettext(), 'Продажба')) {
            $browser->press('Продажба');
        } else {
            $browser->press('Нов...');
            $browser->press('Продажба');
        }
         
        //$browser->hasText('Създаване на продажба');
        $browser->setValue('reff', 'MinkP');
        $browser->setValue('note', 'MinkPSaleVatInclude');
        $browser->setValue('paymentMethodId', "До 3 дни след фактуриране");
        $browser->setValue('bankAccountId', '#BG11CREX92603114548401');
        $browser->setValue('caseId', '');
        $browser->setValue('chargeVat', "Включено ДДС в цените");
        // Записване черновата на продажбата
        $browser->press('Чернова');
    
        // Добавяне на артикул
        $browser->press('Артикул');
        $browser->setValue('productId', 'Други стоки');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '23');
        $browser->setValue('packPrice', '1,12');
        $browser->setValue('discount', 10);
    
        // Записване артикула и добавяне нов - услуга
        $browser->press('Запис и Нов');
        $browser->setValue('productId', 'Други услуги');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', 10);
        $browser->setValue('packPrice', 1.1124);
        $browser->setValue('discount', 10);
    
        // Записване на артикула
        $browser->press('Запис');
        
        // активиране на продажбата
        $browser->press('Активиране');
        $browser->press('Активиране/Контиране');
         
        if(strpos($browser->gettext(), 'Отстъпка: BGN 3,69')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна отстъпка', 'warning');
        }
        if(strpos($browser->gettext(), 'Тридесет и три BGN и 0,19')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна обща сума', 'warning');
        }
    
        // Когато няма автом. избиране - ЕН и протокол
                
        // Фактура
        $browser->press('Фактура');
        $browser->press('Чернова');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Данъчна основа: BGN 27,66')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна данъчна основа във фактура', 'warning');
        }
        if(strpos($browser->gettext(), 'ДДС: BGN 5,53')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешно ДДС във фактура', 'warning');
        }
        
        if(strpos($browser->gettext(), 'Плащане по банков път')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешен начин на плащане', 'warning');
        }
        
        // Клониране
        $browser->press('Клониране');
        $browser->press('Запис');
        if(strpos($browser->gettext(), 'Ваш реф: MinkPv2')) {
        } else {
            return unit_MinkPbgERP::reportErr('Неуспешно клониране', 'warning');
        }    
        // активиране на продажбата
        $browser->press('Активиране');
        $browser->press('Активиране/Контиране');
        
    }
       
    /**
    * 10. Продажба EUR - освободена от ДДС - Sal13
    */
         
    //http://localhost/unit_MinkPSales/CreateSaleEURVatFree3/
    function act_CreateSaleEURVatFree3()
    {
        // Логване
        $browser = $this->SetUp();
        
        //Отваряме папката на фирмата
        $browser = $this->SetFirmEUR();
        
        // нова продажба - проверка има ли бутон
        if(strpos($browser->gettext(), 'Продажба')) {
            $browser->press('Продажба');
        } else {
            $browser->press('Нов...');
            $browser->press('Продажба');
        }
        $enddate=strtotime("+2 Days");
        $browser->setValue('reff', 'MinkP');
        $browser->setValue('bankAccountId', '');
        $browser->setValue('note', 'MinkPSaleEURVatFree3');
        $browser->setValue('paymentMethodId', "До 3 дни след фактуриране");
        $browser->setValue('chargeVat', 'exempt');
        //$browser->setValue('chargeVat', "Oсвободено от ДДС");//Ако контрагентът е от България дава грешка 234 - NodeElement.php
        // Записване черновата на продажбата
        $browser->press('Чернова');
        
        // Добавяне на артикул
        $browser->press('Артикул');
        $browser->setValue('productId', 'Други стоки');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '23');
        $browser->setValue('packPrice', '1,12');
        $browser->setValue('discount', 10);
        
        // Записване артикула и добавяне нов - услуга
        $browser->press('Запис и Нов');
        $browser->setValue('productId', 'Други услуги');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', 10);
        $browser->setValue('packPrice', 1.1124);
        $browser->setValue('discount', 10);
        // Записване на артикула
        $browser->press('Запис');
         
        // активиране на продажбата
        $browser->press('Активиране');
        $browser->press('Активиране/Контиране');
         
        if(strpos($browser->gettext(), '3,69')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна отстъпка', 'warning');
        }
        if(strpos($browser->gettext(), 'Thirty-three EUR and 0,19')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна обща сума', 'warning');
        }    
        
        // Когато няма автом. избиране
        // ЕН
        // протокол
        // Фактура
        $browser->press('Фактура');
        $browser->setValue('vatReason', 'чл.53 от ЗДДС – ВОД');
        $browser->press('Чернова');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Tax base: BGN 64,91')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна данъчна основа във фактурата', 'warning');
        }
       
    }
    
    /**
     * 11. Продажба EUR - освободена от ДДС, авансово пл. Sal14
     */
     
    //http://localhost/unit_MinkPSales/CreateSaleEURVatFreeAdv/
    function act_CreateSaleEURVatFreeAdv()
    {
        // Логване
        $browser = $this->SetUp();
    
        //Отваряме папката на фирмата
        $browser = $this->SetFirmEUR();
        
        // нова продажба - проверка има ли бутон
        if(strpos($browser->gettext(), 'Продажба')) {
            $browser->press('Продажба');
        } else {
            $browser->press('Нов...');
            $browser->press('Продажба');
        }
        $browser->setValue('reff', 'MinkP');
        $browser->setValue('bankAccountId', '');
        $browser->setValue('note', 'MinkPSaleVatFreeAdv');
        $browser->setValue('paymentMethodId', "100% авансово");
        //$browser->setValue('chargeVat', "Oсвободено от ДДС");//Ако контрагентът е от България дава грешка 234 - NodeElement.php
        $browser->setValue('chargeVat', 'exempt');
        // Записване черновата на продажбата
        $browser->press('Чернова');
    
        // Добавяне на артикул
        $browser->press('Артикул');
        $browser->setValue('productId', 'Други стоки');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '3');
        $browser->setValue('packPrice', '1,123');
        $browser->setValue('discount', 2);
    
        $browser->press('Запис');
         
        // активиране на продажбата
        $browser->press('Активиране');
        //$browser->press('Активиране/Контиране');
         
        if(strpos($browser->gettext(), 'Discount: EUR 0,07')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна отстъпка', 'warning');
        }
        if(strpos($browser->gettext(), 'Three EUR and 0,30')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна обща сума', 'warning');
        }
        
        // Фактура
        $browser->press('Фактура');
        $browser->setValue('vatReason', 'чл.53 от ЗДДС – ВОД');
        $browser->setValue('amountAccrued', '3.3');
        $browser->press('Чернова');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Tax base: BGN 6,45')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна данъчна основа във фактура 1', 'warning');
        }
        // ПБД
        $browser->press('ПБД');
        $browser->setValue('ownAccount', '#BG11CREX92603114548401');
        $browser->setValue('amountDeal', '3,3');
        $browser->press('Чернова');
        $browser->press('Контиране');
        
        // експедиционно нареждане
        $browser->press('Експедиране');
        $browser->setValue('storeId', 'Склад 1');
        $browser->setValue('template', 'Експедиционно нареждане с цени');
        $browser->press('Чернова');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Словом: Три EUR и 0,30')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна сума в ЕН', 'warning');
        }
         
        // Фактура
        $browser->press('Фактура');
        $browser->setValue('vatReason', 'чл.53 от ЗДДС – ВОД');
        $browser->setValue('amountDeducted', '3.3');
        $browser->press('Чернова');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Tax base: BGN 0,00')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна данъчна основа във фактура 2', 'warning');
        }
    
       // Приключване
        $browser->press('Приключване');
        $browser->setValue('valiorStrategy', 'Най-голям вальор в нишката');
        $browser->press('Чернова');
        $browser->press('Контиране');
        
    }
    
    /**
     * 11. Продажба - Кредитно и дебитно известие (Sal12)
     */
     
    //http://localhost/unit_MinkPSales/CreateCreditDebitInvoice/
    function act_CreateCreditDebitInvoice()
    {
    
        // Логване
        $browser = $this->SetUp();
    
        //Отваряне папката на фирмата
         $browser = $this->SetFirm();
    
        // нова продажба - проверка има ли бутон
        if(strpos($browser->gettext(), 'Продажба')) {
            $browser->press('Продажба');
        } else {
            $browser->press('Нов...');
            $browser->press('Продажба');
        }
         
        //$browser->hasText('Създаване на продажба');
        $browser->setValue('reff', 'MinkP');
        $browser->setValue('bankAccountId', '');
        $browser->setValue('note', 'MinkPSaleCIDI');
        $browser->setValue('paymentMethodId', "До 3 дни след фактуриране");
        $browser->setValue('chargeVat', "Включено ДДС в цените");
        // Записване черновата на продажбата
        $browser->press('Чернова');
    
        // Добавяне на артикул
        $browser->press('Артикул');
        $browser->setValue('productId', 'Други стоки');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '40');
        $browser->setValue('packPrice', '2,6');
        $browser->setValue('discount', 10);
    
        // Записване на артикула
        $browser->press('Запис');
        
        // активиране на продажбата
        $browser->press('Активиране');
        $browser->press('Активиране/Контиране');
         
        if(strpos($browser->gettext(), '10,40')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна отстъпка', 'warning');
        }
        if(strpos($browser->gettext(), 'Деветдесет и три BGN и 0,60')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна обща сума', 'warning');
        }
    
        // експедиционно нареждане
        //$browser->press('Експедиране');
        //$browser->setValue('storeId', 'Склад 1');
        //$browser->setValue('template', 'Експедиционно нареждане с цени');
        //$browser->press('Чернова');
        //$browser->press('Контиране');
                 
        // Фактура
        $browser->press('Фактура');
        $browser->press('Чернова');
        $browser->press('Контиране');
        
        // Кредитно известие - сума
        $browser->press('Известие');
        $browser->setValue('changeAmount', '-22.36');
        $browser->press('Чернова');
       
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Минус двадесет и шест BGN и 0,83')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна сума в КИ - сума', 'warning');
        }
        
        // Кредитно известие - количество
        $browser->press('Известие');
        $browser->press('Чернова');
        //$browser->click('Редактиране на артикул');
        $browser->click('edt12');
        //намира арт. от фактурата
        $browser->setValue('quantity', '20');
        
        $browser->press('Запис');
        
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Минус четиридесет и шест BGN и 0,80')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна сума в КИ - количество', 'warning');
        }
        
        // Кредитно известие - цена
        $browser->press('Известие');
        $browser->press('Чернова');
        //$browser->click('Редактиране на артикул');
        $browser->click('edt13');
        $browser->setValue('packPrice', '1.4444');
        $browser->press('Запис');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Минус двадесет и четири BGN и 0,26')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна сума в КИ - цена', 'warning');
        }
        
        // Дебитно известие - сума
        $browser->press('Известие');
        $browser->setValue('changeAmount', '22.20');
        $browser->press('Чернова');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Двадесет и шест BGN и 0,64')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна сума в ДИ - сума', 'warning');
        }
        // Дебитно известие - количество
        $browser->press('Известие');
        $browser->press('Чернова');
        //$browser->click('Редактиране на артикул');
        $browser->click('edt14');
        $browser->setValue('quantity', '50');
        $browser->press('Запис');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Двадесет и три BGN и 0,40')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна сума в ДИ - количество', 'warning');
        }
        // Дебитно известие - цена
        $browser->press('Известие');
        $browser->press('Чернова');
        //$browser->click('Редактиране на артикул');
        $browser->click('edt15');
        $browser->setValue('packPrice', '2.5556');
        $browser->press('Запис');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Двадесет и девет BGN и 0,06')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна сума в ДИ - цена', 'warning');
        }
       
    }  
    
    /**
     * 12. Продажба - Кредитно и дебитно известие - освободено от ДДС (валута) (Sal13)
     */ 
     
    //http://localhost/unit_MinkPSales/CreateCreditDebitInvoiceVATFree/
    function act_CreateCreditDebitInvoiceVATFree()
    {
    
        // Логване
        $browser = $this->SetUp();
    
        //Отваряне папката на фирмата
        $browser = $this->SetFirmEUR();
    
        // нова продажба - проверка има ли бутон
        if(strpos($browser->gettext(), 'Продажба')) {
            $browser->press('Продажба');
        } else {
            $browser->press('Нов...');
            $browser->press('Продажба');
        }
         
        //$browser->hasText('Създаване на продажба');
        $browser->setValue('reff', 'MinkP');
        $browser->setValue('bankAccountId', '');
        $browser->setValue('note', 'MinkPSaleCIDICVATFree');
        $browser->setValue('paymentMethodId', "До 3 дни след фактуриране");
        //$browser->setValue('chargeVat', "Oсвободено от ДДС");
        $browser->setValue('chargeVat', 'exempt');
        // Записване черновата на продажбата
        $browser->press('Чернова');
    
        // Добавяне на артикул
        $browser->press('Артикул');
        $browser->setValue('productId', 'Други стоки');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '40');
        $browser->setValue('packPrice', '2,6');
        $browser->setValue('discount', 10);
    
        // Записване на артикула
        $browser->press('Запис');
               
        // активиране на продажбата
        $browser->press('Активиране');
        $browser->press('Активиране/Контиране');
         
        if(strpos($browser->gettext(), 'Discount: EUR 10,40')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна отстъпка', 'warning');
        }
        if(strpos($browser->gettext(), 'Ninety-three EUR and 0,60')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна обща сума', 'warning');
        }
    
        // експедиционно нареждане
        //$browser->press('Експедиране');
        //$browser->setValue('storeId', 'Склад 1');
        //$browser->setValue('template', 'Експедиционно нареждане с цени');
        //$browser->press('Чернова');
        //$browser->press('Контиране');
         
        // Фактура
        $browser->press('Фактура');
        $browser->setValue('vatReason', 'чл.53 от ЗДДС – ВОД');
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        // Кредитно известие - сума
        $browser->press('Известие');
        $browser->setValue('changeAmount', '-22.36');
        $browser->press('Чернова');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Minus twenty-two EUR and 0,36')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна сума в КИ - сума', 'warning');
        }
        if(strpos($browser->gettext(), 'Amount reducing')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешка в КИ - текст', 'warning');
        }
        // Кредитно известие - количество
        $browser->press('Известие');
        $browser->press('Чернова');
        //$browser->click('Edit');
        $browser->click('edt17');
        $browser->setValue('quantity', '20');
        $browser->press('Запис');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Minus forty-six EUR and 0,80')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна сума в КИ - количество', 'warning');
        }
    
        // Кредитно известие - цена
        $browser->press('Известие');
        $browser->press('Чернова');
        //$browser->click('Edit');
        $browser->click('edt18');
        $browser->setValue('packPrice', '1.4444');
        $browser->press('Запис');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Minus thirty-five EUR and 0,82')) {
        } else {
            return unit_MinkPbgERP::reportErr('сума в КИ - цена', 'warning');
        }
    
        // Дебитно известие - сума
        $browser->press('Известие');
        $browser->setValue('changeAmount', '22.20');
        $browser->press('Чернова');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Twenty-two EUR and 0,20')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна сума в ДИ - сума', 'warning');
        }
        if(strpos($browser->gettext(), 'Amount increasing')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешка в ДИ - текст', 'warning');
        }
        
        // Дебитно известие - количество
        $browser->press('Известие');
        $browser->press('Чернова');
        //$browser->click('Edit');
        $browser->click('edt19');
        $browser->setValue('quantity', '50');
        $browser->press('Запис');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Twenty-three EUR and 0,40')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна сума в ДИ - количество', 'warning');
        }
        
        // Дебитно известие - цена
        $browser->press('Известие');
        $browser->press('Чернова');
        //$browser->click('Edit');
        $browser->click('edt20');
        $browser->setValue('packPrice', '2.6667');
        $browser->press('Запис');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Thirteen EUR and 0,07')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна сума в ДИ - цена', 'warning');
        }
        
    }
    
    /**
     * 13. Продажба - Кредитно и дебитно известие без ДДС (валута)
     */
     
    //http://localhost/unit_MinkPSales/CreateCreditDebitInvoiceVATNo/
    function act_CreateCreditDebitInvoiceVATNo()
    {
    
        // Логване
        $browser = $this->SetUp();
    
        //Отваряне папката на фирмата
        $browser = $this->SetFirmEUR();
    
        // нова продажба - проверка има ли бутон
        if(strpos($browser->gettext(), 'Продажба')) {
            $browser->press('Продажба');
        } else {
            $browser->press('Нов...');
            $browser->press('Продажба');
        }
         
        $browser->setValue('reff', 'MinkP');
        $browser->setValue('bankAccountId', '');
        $browser->setValue('note', 'MinkPSaleCIDICVATNo');
        $browser->setValue('paymentMethodId', "До 3 дни след фактуриране");
        //$browser->setValue('chargeVat', "Без начисляване на ДДС");
        $browser->setValue('chargeVat', 'no');
        // Записване черновата на продажбата
        $browser->press('Чернова');
    
        // Добавяне на артикул
        $browser->press('Артикул');
        $browser->setValue('productId', 'Други стоки');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '40');
        $browser->setValue('packPrice', '2,6');
        $browser->setValue('discount', 10);
    
        // Записване на артикула
        $browser->press('Запис');
    
        // активиране на продажбата
        $browser->press('Активиране');
        $browser->press('Активиране/Контиране');
         
        if(strpos($browser->gettext(), 'Discount: EUR 10,40')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна отстъпка', 'warning');
        }
        if(strpos($browser->gettext(), 'Ninety-three EUR and 0,60')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна обща сума', 'warning');
        }
    
        // експедиционно нареждане
        //$browser->press('Експедиране');
        //$browser->setValue('storeId', 'Склад 1');
        //$browser->setValue('template', 'Експедиционно нареждане с цени');
        //$browser->press('Чернова');
        //$browser->press('Контиране');
         
        // Фактура
        $browser->press('Фактура');
        $browser->setValue('vatReason', 'чл.53 от ЗДДС – ВОД');
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        // Кредитно известие - сума
        $browser->press('Известие');
        $browser->setValue('changeAmount', '-22.36');
        $browser->press('Чернова');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Minus twenty-two EUR and 0,36 ')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна сума в КИ - сума', 'warning');
        }
        if(strpos($browser->gettext(), 'Amount reducing')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешка в КИ - текст', 'warning');
        }
        
        // Кредитно известие - количество
        $browser->press('Известие');
        $browser->press('Чернова');
        //$browser->click('Edit');
        $browser->click('edt22');
        $browser->setValue('quantity', '20');
        $browser->press('Запис');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Minus forty-six EUR and 0,80')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна сума в КИ - количество', 'warning');
        }
    
        // Кредитно известие - цена
        $browser->press('Известие');
        $browser->press('Чернова');
        //$browser->click('Edit');
        $browser->click('edt23');
        $browser->setValue('packPrice', '1.4444');
        $browser->press('Запис');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Minus thirty-five EUR and 0,82')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна сума в КИ - цена', 'warning');
        }
    
        // Дебитно известие - сума 
        $browser->press('Известие');
        $browser->setValue('changeAmount', '22.20');
        $browser->press('Чернова');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Twenty-two EUR and 0,20')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна сума в ДИ - сума', 'warning');
        }
        if(strpos($browser->gettext(), 'Amount increasing')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешка в ДИ - текст', 'warning');
        }
        
        // Дебитно известие - количество
        $browser->press('Известие');
        $browser->press('Чернова');
        //$browser->click('Edit');
        $browser->click('edt24');
        $browser->setValue('quantity', '50');
        $browser->press('Запис');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Twenty-three EUR and 0,40')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна сума в ДИ - количество', 'warning');
        }
    
        // Дебитно известие - цена
        $browser->press('Известие');
        $browser->press('Чернова');
        //$browser->click('Edit');
        $browser->click('edt25');
        $browser->setValue('packPrice', '2.6667');
        $browser->press('Запис');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Thirteen EUR and 0,07')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна сума в ДИ - цена', 'warning');
        }
        
    }
    
    /**
     * 14. Продажба - Кредитно и дебитно известие с ДДС (валута)
     */
     
    //http://localhost/unit_MinkPSales/CreateCreditDebitInvoiceVATYes/
    function act_CreateCreditDebitInvoiceVATYes()
    {
    
        // Логване
        $browser = $this->SetUp();
    
        //Отваряне папката на лицето
        $browser->click('Визитник');
        $browser->click('Лица');
        $browser->click('S');
        $person = "Sam Wilson";
        $browser->click($person);
        $browser->press('Папка');
        
        // нова продажба - проверка има ли бутон
        if(strpos($browser->gettext(), 'Продажба')) {
            $browser->press('Продажба');
        } else {
            $browser->press('Нов...');
            $browser->press('Продажба');
        }
         
        //$browser->hasText('Създаване на продажба');
        $browser->setValue('reff', 'MinkP');
        $browser->setValue('bankAccountId', '');
        $browser->setValue('note', 'MinkPSaleCIDICVAT');
        $browser->setValue('paymentMethodId', "До 3 дни след фактуриране");
        $browser->setValue('bankAccountId', '#BG22UNCR70001519562302');
        $browser->setValue('chargeVat', 'yes');
        // Записване черновата на продажбата, игнориране предупреждението за ДДС
        
        $browser->press('Чернова');
        $browser->setValue('Ignore', '1');
        $browser->press('Чернова');
        
        // Добавяне на артикул
        $browser->press('Артикул');
        $browser->setValue('productId', 'Други стоки');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '90');
        $browser->setValue('packPrice', '1,2');
        $browser->setValue('discount', 2);
    
        // Записване на артикула
        $browser->press('Запис');
    
        // активиране на продажбата
        $browser->press('Активиране');
        $browser->press('Активиране/Контиране');
         
        if(strpos($browser->gettext(), 'Discount: USD 2,16')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна отстъпка', 'warning');
        }
        if(strpos($browser->gettext(), 'One hundred and five USD and 0,84')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна обща сума', 'warning');
        }
    
        // експедиционно нареждане
        //$browser->press('Експедиране');
        //$browser->setValue('storeId', 'Склад 1');
        //$browser->press('Чернова');
        //$browser->press('Контиране');
         
        // Фактура
        $browser->press('Фактура');
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        // Кредитно известие - сума
        $browser->press('Известие');
        $browser->setValue('changeAmount', '-22.36');
        $browser->press('Чернова');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Minus twenty-six USD and 0,83')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна сума в КИ - сума', 'warning');
        }
        if(strpos($browser->gettext(), 'Amount reducing')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешка в КИ - текст', 'warning');
        }
        // Кредитно известие - количество
        $browser->press('Известие');
        $browser->press('Чернова');
        //$browser->click('Edit');
        $browser->click('edt27');
        $browser->setValue('quantity', '20');
        $browser->press('Запис');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Minus eighty-two USD and 0,32')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна сума в КИ - количество', 'warning');
        }
    
        // Кредитно известие - цена
        $browser->press('Известие');
        $browser->press('Чернова');
        //$browser->click('Edit');
        $browser->click('edt28');
        $browser->setValue('packPrice', '0.8');
        $browser->press('Запис');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Minus nineteen USD and 0,44')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна сума в КИ - цена', 'warning');
        }
    
        // Дебитно известие - сума
        $browser->press('Известие');
        $browser->setValue('changeAmount', '22.20');
        $browser->press('Чернова');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Twenty-six USD and 0,64')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна сума в ДИ - сума', 'warning');
        }
        if(strpos($browser->gettext(), 'Amount increasing')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешка в ДИ - текст', 'warning');
        }
    
        // Дебитно известие - количество
        $browser->press('Известие');
        $browser->press('Чернова');
        //$browser->click('Edit');
        $browser->click('edt29');
        $browser->setValue('quantity', '100');
        $browser->press('Запис');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Eleven USD and 0,76')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна сума в ДИ - количество', 'warning');
        }
    
        // Дебитно известие - цена
        $browser->press('Известие');
        $browser->press('Чернова');
        //$browser->click('Edit');
        $browser->click('edt30');
        $browser->setValue('packPrice', '1.3');
        $browser->press('Запис');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Thirty-four USD and 0,56')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна сума в ДИ - цена', 'warning');
        }
        
    }
    /**
     * 15. Продажба - Кредитно известие за цялата сума с различно ДДС (валута)
     */
     
    //http://localhost/unit_MinkPSales/CreateCreditInvoiceDiffVATYes/
    function act_CreateCreditInvoiceDiffVATYes()
    {
    
        // Логване
        $browser = $this->SetUp();
    
        //Отваряне папката на лицето
        $browser->click('Визитник');
        $browser->click('Лица');
        $browser->click('S');
        $person = "Sam Wilson";
        $browser->click($person);
        $browser->press('Папка');
    
        // нова продажба - проверка има ли бутон
        if(strpos($browser->gettext(), 'Продажба')) {
            $browser->press('Продажба');
        } else {
            $browser->press('Нов...');
            $browser->press('Продажба');
        }
         
        //$browser->hasText('Създаване на продажба');
        $browser->setValue('reff', 'MinkP');
        $browser->setValue('bankAccountId', '');
        $browser->setValue('note', 'MinkPSaleCIDiffVAT');
        $browser->setValue('paymentMethodId', "До 3 дни след фактуриране");
        $browser->setValue('chargeVat', 'yes');
        // Записване черновата на продажбата
        $browser->press('Чернова');
    
        // Добавяне на артикул
        $browser->press('Артикул');
        $browser->setValue('productId', 'Други стоки');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '90');
        $browser->setValue('packPrice', '2,266');
        $browser->setValue('discount', 1);
        $browser->press('Запис и Нов');
        // Записваме артикула и добавяме нов
        $browser->setValue('productId', 'Чувал голям 50 L');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '3');
        $browser->setValue('packPrice', '09,25/0,3*04');//123,3333
        $browser->setValue('discount', 2);
        
        $browser->press('Запис и Нов');
        // Записваме артикула и добавяме нов
        $browser->setValue('productId', 'Артикул ДДС 9');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '08.0');
        $browser->setValue('packPrice', '01,00+3.1456*0.8');//3,51648
        $browser->setValue('discount', 3);
        // Записване на артикула
        $browser->press('Запис');
    
        // активиране на продажбата
        $browser->press('Активиране');
        $browser->press('Активиране/Контиране');
         
        if(strpos($browser->gettext(), 'Discount: USD 10,29')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна отстъпка', 'warning');
        }
        if(strpos($browser->gettext(), 'Five hundred and ninety-one USD and 0,78')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна обща сума', 'warning');
        }
    
        /* ДДС групата сработва от следващия ден, а фактура не може да се издаде със следваща дата
        // Фактура
        $browser->press('Фактура');
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        // Кредитно известие за цялата сума
        $browser->press('Известие');
        $browser->press('Чернова');
        //$browser->click('Edit');
        $browser->click('edt31');
        $browser->setValue('quantity', '0');
        $browser->press('Следващ');
        // зануляване на кол. на втория артикул
        $browser->setValue('quantity', '0');
        $browser->press('Следващ');
        // зануляване на кол. на третия артикул
        $browser->setValue('quantity', '0');
        $browser->press('Запис');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Minus five hundred and ninety-one USD and 0,78')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна сума в КИ за цялото количество', 'warning');
        }
        */
    }
    
    /**
     * 15. Продажба - Кредитно известие за авансово плащане, връщане на стоката - СР и РБД
     */
     
    //http://localhost/unit_MinkPSales/CreateCreditInvoiceAdvPayment/
    function act_CreateCreditInvoiceAdvPayment()
    {
        // Логване
        $browser = $this->SetUp();
        
        //Отваряме папката на фирмата
        $browser = $this->SetFirm();
        
        // нова продажба - проверка има ли бутон
        if(strpos($browser->gettext(), 'Продажба')) {
            $browser->press('Продажба');
        } else {
            $browser->press('Нов...');
            $browser->press('Продажба');
        }
        $browser->setValue('reff', 'MinkP');
        $browser->setValue('bankAccountId', '');
        $browser->setValue('note', 'MinkPSaleCreditInvoiceAdv');
        $browser->setValue('paymentMethodId', "100% авансово");
        //$browser->setValue('chargeVat', "Отделен ред за ДДС");
        $browser->setValue('chargeVat', "Включено ДДС в цените");
        // Записване черновата на продажбата
        $browser->press('Чернова');
        
        // Добавяне на артикул
        $browser->press('Артикул');
        $browser->setValue('productId', 'Други стоки');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '3');
        $browser->setValue('packPrice', '10,4123');
        $browser->setValue('discount', 3);
        
        $browser->press('Запис');
         
        // активиране на продажбата
        $browser->press('Активиране');
        //$browser->press('Активиране/Контиране');
         
        if(strpos($browser->gettext(), 'Отстъпка: BGN 0,94')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна отстъпка', 'warning');
        }
        if(strpos($browser->gettext(), 'Тридесет BGN и 0,30')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна обща сума', 'warning');
        }
        
        // Фактура
        $browser->press('Фактура');
        $browser->setValue('amountAccrued', '30.3');
        $browser->press('Чернова');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Авансово плащане по договор')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешка в текста на фактурата', 'warning');
        }
        
        if(strpos($browser->gettext(), 'Данъчна основа: BGN 25,25')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна данъчна основа във фактура 1', 'warning');
        }
        // ПБД
        $browser->press('ПБД');
        $browser->setValue('ownAccount', '#BG11CREX92603114548401');
        $browser->setValue('amountDeal', '30,3');
        $browser->press('Чернова');
        $browser->press('Контиране');
        
        // експедиционно нареждане
        $browser->press('Експедиране');
        $browser->setValue('storeId', 'Склад 1');
        $browser->press('Чернова');
        $browser->press('Контиране');
                
        // Кредитно известие - сума
        $browser->press('Известие');
        $browser->setValue('changeAmount', '-25,25');
        $browser->press('Чернова');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Словом: Минус тридесет BGN и 0,30')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна сума в КИ - сума', 'warning');
        }
        if(strpos($browser->gettext(), 'Намаляване на авансово плащане')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешка в КИ - текст', 'warning');
        }
        
        // Складова разписка
        $browser->press('Нов');
        $browser->press('Складова разписка');
        $browser->setValue('storeId', 'Склад 1');
        $browser->press('Чернова');
        $browser->press('Артикул');
        $browser->setValue('productId', 'Други стоки');
        $browser->press('Refresh');
        $browser->setValue('packQuantity', '3');
        $browser->press('Запис');
        $browser->press('Контиране');
        
        // РБД
        $browser->press('Нов');
        $browser->press('Разходен банков документ');
        $browser->setValue('ownAccount', '#BG11CREX92603114548401');
        $browser->setValue('amountDeal', '30,3');
        $browser->press('Чернова');
        $browser->press('Контиране');
        
        // Приключване
        $browser->press('Приключване');
        $browser->setValue('valiorStrategy', 'Най-голям вальор в нишката');
        $browser->press('Чернова');
        $browser->press('Контиране');
        
        //Проверка на статистиката
        if(strpos($browser->gettext(), '30,30 0,00 0,00 0,00')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешни суми в мастера', 'warning');
        }
        
    }
    
    /**
     * 16. Продажба - схема с авансово плащане, Включено ДДС в цените
     * Проверка състояние чакащо плащане - не (платено)
     */
     
    //http://localhost/unit_MinkPSales/CreateSaleAdvPaymentInclVAT/
    function act_CreateSaleAdvPaymentInclVAT()
    {
    
        // Логваме се
        $browser = $this->SetUp();
    
        //Отваряме папката на фирмата
         $browser = $this->SetFirm();
    
        // нова продажба - проверка има ли бутон
        if(strpos($browser->gettext(), 'Продажба')) {
            $browser->press('Продажба');
        } else {
            $browser->press('Нов...');
            $browser->press('Продажба');
        }
         
        //$browser->hasText('Създаване на продажба');
        $browser->setValue('reff', 'MinkP');
        $browser->setValue('bankAccountId', '');
        $browser->setValue('template', 'Договор за продажба');
        $browser->setValue('note', 'MinkPAdvancePaymentInclVAT');
        $browser->setValue('paymentMethodId', "20% авансово и 80% преди експедиция");
        $browser->setValue('chargeVat', "Включено ДДС в цените");
         
        // Записваме черновата на продажбата
        $browser->press('Чернова');
        $browser->press('Артикул');
        // Добавяме нов артикул
        $browser->setValue('productId', 'Други стоки');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '17');
        $browser->setValue('packPrice', '6.325');
        $browser->setValue('discount', 3);
    
        // Записваме артикула и добавяме нов - услуга
        $browser->press('Запис и Нов');
        $browser->setValue('productId', 'Други услуги');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', 113);
        $browser->setValue('packPrice', 1.0224);
        $browser->setValue('discount', 1);
     
        // Записваме артикула
        $browser->press('Запис');
        // активираме продажбата
        $browser->press('Активиране');
        //$browser->press('Активиране/Контиране');
         
        if(strpos($browser->gettext(), 'Авансово: BGN 43,73')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешно авансово плащане', 'warning');
        }
    
        if(strpos($browser->gettext(), 'Двеста и осемнадесет BGN и 0,67')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна обща сума', 'warning');
        }
       
        // ПБД
        $browser->press('ПБД');
        $browser->setValue('ownAccount', '#BG11CREX92603114548401');
        $browser->setValue('amountDeal', '43,73');
        $browser->press('Чернова');
        $browser->press('Контиране');
        
        // Фактура
        $browser->press('Фактура');
        $browser->press('Чернова');
        $browser->press('Контиране');
        
        // ПБД
        $browser->press('ПБД');
        $browser->setValue('ownAccount', '#BG11CREX92603114548401');
        $browser->setValue('amountDeal', '174,94');
        $browser->press('Чернова');
        $browser->press('Контиране');
        
        // експедиционно нареждане
        $browser->press('Експедиране');
        $browser->setValue('storeId', 'Склад 1');
        $browser->setValue('template', 'Експедиционно нареждане с цени');
        $browser->press('Чернова');
        $browser->press('Контиране');
        //if(strpos($browser->gettext(), 'Контиране')) {
        //}
        if(strpos($browser->gettext(), 'Сто и четири BGN и 0,29')) {
         } else {
            return unit_MinkPbgERP::reportErr('Грешна сума в ЕН', 'warning');
        }
         
        // протокол
        $browser->press('Пр. услуги');
        $browser->press('Чернова');
        $browser->press('Контиране');
       
        // Фактура
        $browser->press('Фактура');
        $browser->press('Чернова');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), '-36,44')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна сума за приспадане', 'warning');
        }
        if(strpos($browser->gettext(), 'Данъчна основа: BGN 145,79')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна данъчна основа във фактура', 'warning');
        }
        if(strpos($browser->gettext(), 'ДДС: BGN 29,15')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешно ДДС във фактура', 'warning');
        }    
        
       // Приключване
        $browser->press('Приключване');
        $browser->setValue('valiorStrategy', 'Най-голям вальор в нишката');
        $browser->press('Чернова');
        $browser->press('Контиране');
        
        //Проверка на статистиката
        if(strpos($browser->gettext(), '218,67 218,67 218,67 218,67')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешни суми в мастера', 'warning');
        }
        
    }
    
    /**
     * 17. Продажба - схема с авансово плащане, отделно ДДС
     * Проверка състояние чакащо плащане - не (платено)
     */
     
    //http://localhost/unit_MinkPSales/CreateSaleAdvPaymentSep/
    function act_CreateSaleAdvPaymentSep()
    {
    
        // Логваме се
        $browser = $this->SetUp();
    
        //Отваряме папката на фирмата
        $browser = $this->SetFirm();
    
        // нова продажба - проверка има ли бутон
        if(strpos($browser->gettext(), 'Продажба')) {
            $browser->press('Продажба');
        } else {
            $browser->press('Нов...');
            $browser->press('Продажба');
        }
         
        //$browser->hasText('Създаване на продажба');
        $browser->setValue('reff', 'MinkP');
        $browser->setValue('bankAccountId', '');
        $browser->setValue('note', 'MinkPAdvancePayment');
        $browser->setValue('paymentMethodId', "20% авансово и 80% преди експедиция");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
         
        // Записваме черновата на продажбата
        $browser->press('Чернова');
    
        // Добавяме нов артикул
        $browser->press('Артикул');
        $browser->setValue('productId', 'Чувал голям 50 L');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '100');
        $browser->setValue('packPrice', '10');
        
        // Записваме артикула
        $browser->press('Запис');
        // активираме продажбата
        $browser->press('Активиране');
        //$browser->press('Активиране/Контиране');
         
        if(strpos($browser->gettext(), 'Авансово: BGN 240,00')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешно авансово плащане', 'warning');
        }
    
        if(strpos($browser->gettext(), 'Хиляда и двеста BGN')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна обща сума', 'warning');
        }
       
        // Проформа
        $browser->press('Проформа');
        $browser->setValue('amountAccrued', '240');
        $browser->press('Чернова');
        $browser->press('Активиране');
       
        // ПБД
        $browser->press('ПБД');
        $browser->setValue('ownAccount', '#BG11CREX92603114548401');
        $browser->setValue('amountDeal', '240');
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        // Фактура
        $browser->press('Фактура');
        $browser->press('Чернова');
        $browser->press('Контиране');

        if(strpos($browser->gettext(), 'Двеста и четиридесет BGN')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна сума във фактурата за аванс', 'warning');
        }
       
        // ПБД
        $browser->press('ПБД');
        $browser->setValue('ownAccount', '#BG11CREX92603114548401');
        $browser->setValue('amountDeal', '960.00');
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        // експедиционно нареждане
        $browser->press('Експедиране');
        $browser->setValue('storeId', 'Склад 1');
        $browser->setValue('template', 'Експедиционно нареждане с цени');
        $browser->press('Чернова');
        $browser->press('Контиране');
       
        // Фактура
        $browser->press('Фактура');
        $browser->press('Чернова');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), '-200,00')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна сума за приспадане', 'warning');
        }
         
        // Приключване
        $browser->press('Приключване');
        $browser->setValue('valiorStrategy', 'Най-голям вальор в нишката');
        $browser->press('Чернова');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Чакащо плащане: Няма')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешно чакащо плащане', 'warning');
        }
       
    }
    
    /**
     * 18. Продажба на артикули с различно ДДС
     */
     
    //http://localhost/unit_MinkPSales/CreateSaleDifVAT/
    function act_CreateSaleDifVAT()
    {
    
        // Логваме се
        $browser = $this->SetUp();
    
        //Отваряме папката на фирмата
        $browser = $this->SetFirm();
    
        // нова продажба - проверка има ли бутон
        if(strpos($browser->gettext(), 'Продажба')) {
            $browser->press('Продажба');
        } else {
            $browser->press('Нов...');
            $browser->press('Продажба');
        }
         
        //$browser->hasText('Създаване на продажба');
        $browser->setValue('reff', 'MinkP');
        $valior=strtotime("+1 Day");
        $browser->setValue('valior', date('d-m-Y', $valior));
        $browser->setValue('bankAccountId', '');
        $browser->setValue('note', 'MinkPDifVAT');
        $browser->setValue('paymentMethodId', "До 3 дни след фактуриране");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
         
        // Записваме черновата на продажбата
        $browser->press('Чернова');
        if(strpos($browser->gettext(), 'Датата е в несъществуващ счетоводен период')) {
            $browser->setValue('Игнорирай предупреждениeто', True);
            $browser->press('Чернова');
        }
        
        // Добавяме нов артикул - 20% ДДС
        $browser->press('Артикул');
        $browser->setValue('productId', 'Чувал голям 50 L');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '4');
        $browser->setValue('packPrice', '20');
        $browser->setValue('discount', 20);
        // Записване артикула и добавяне нов
        $browser->press('Запис и Нов');
        $browser->setValue('productId', 'Други стоки');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '1');
        $browser->setValue('packPrice', '1');//
        $browser->setValue('discount', 20);
        $browser->press('Запис и Нов');
        // Записване артикула и добавяне нов - 9% ДДС
        $browser->press('Запис и Нов');
        $browser->setValue('productId', 'Артикул ДДС 9');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '9');
        $browser->setValue('packPrice', '9');
        $browser->setValue('discount', 9);
        // Записваме артикула
        $browser->press('Запис');
        // активираме продажбата
        $browser->press('Активиране');
        $browser->press('Активиране/Контиране');
         
        if(strpos($browser->gettext(), 'ДДС 20%: BGN 12,96')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешно ДДС 20%', 'warning');
        }
        if(strpos($browser->gettext(), 'ДДС 9%: BGN 6,63')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешно ДДС 9%', 'warning');
        }
        if(strpos($browser->gettext(), 'Сто петдесет и осем BGN и 0,10')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна обща сума', 'warning');
        }
         
        /* - ДДС групата сработва от следващия ден, а фактура не може да се издаде със следваша дата
        // Фактура
        $browser->press('Фактура');
        $browser->press('Чернова');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Данъчна основа: BGN 64,80')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна Грешна данъчна основа 20%', 'warning');
        }
        if(strpos($browser->gettext(), 'Данъчна основа 9%: BGN 73,71')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна Грешна данъчна основа 9%', 'warning');
        }
       
        */
    }
     
    /**
     * 19. Проверка извънредни приходи
     * Продажба - Включено ДДС в цените
     */
     
    //http://localhost/unit_MinkPSales/CreateSaleExtraIncome/
    function act_CreateSaleExtraIncome()
    {
    
        // Логваме се
        $browser = $this->SetUp();
    
        //Отваряме папката на фирмата
        $browser = $this->SetFirm();
    
        // нова продажба - проверка има ли бутон
        if(strpos($browser->gettext(), 'Продажба')) {
            $browser->press('Продажба');
        } else {
            $browser->press('Нов...');
            $browser->press('Продажба');
        }
         
        //$browser->hasText('Създаване на продажба');
        $browser->setValue('reff', 'MinkP');
        $browser->setValue('bankAccountId', '');
        $browser->setValue('note', 'MinkPExtraIncome');
        $browser->setValue('paymentMethodId', "До 3 дни след фактуриране");
        $browser->setValue('chargeVat', "Включено ДДС в цените");
         
        // Записваме черновата на продажбата
        $browser->press('Чернова');
    
        // Добавяме артикул
        $browser->press('Артикул');
        $browser->setValue('productId', 'Чувал голям 50 L');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '100');
        $browser->setValue('packPrice', '0,736');
    
        // Записваме артикула
        $browser->press('Запис');
        // активираме продажбата
        $browser->press('Активиране');
        $browser->press('Активиране/Контиране');
         
        if(strpos($browser->gettext(), 'Седемдесет и три BGN и 0,60')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна обща сума', 'warning');
        }
         
        // експедиционно нареждане
        //$browser->press('Експедиране');
        //$browser->setValue('storeId', 'Склад 1');
        //$browser->press('Чернова');
        //$browser->press('Контиране');
        
        // Фактура
        $browser->press('Фактура');
        $browser->press('Чернова');
        $browser->press('Контиране');
        
        // ПБД
        $browser->press('ПБД');
        $browser->setValue('ownAccount', '#BG11CREX92603114548401');
        $browser->setValue('amountDeal', '76,30');
        $browser->press('Чернова');
        $browser->press('Контиране');
        
        // Приключване
        $browser->press('Приключване');
        $browser->setValue('valiorStrategy', 'Най-голям вальор в нишката');
        $browser->press('Чернова');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), '73,60 73,60 76,30 73,60')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешни суми в мастера', 'warning');
        }
        
        if(strpos($browser->gettext(), 'BGN 2,70 BGN 0,00')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна сума извънреден приход', 'warning');
        }
        //Да отваря журнала на приключването, а не на продажбата!!!
        $browser->click('Други действия с този документ[4]');
        //return $browser->getHtml();
        $browser->press('Журнал');
        //if(strpos($browser->gettext(), '2,70 Извънредни приходи - надплатени')) {
        //} else {
        //    return unit_MinkPbgERP::reportErr('Грешkа ', 'warning');
        //}
       
    }
    
    /**
     * 20. Проверка извънредни приходи (с втория ПБД е платена цялата сума, вместо разликата)
     * Продажба - схема с авансово плащане, отделно ДДС
     */
     
    //http://localhost/unit_MinkPSales/CreateSaleAdvExtraIncome/
    function act_CreateSaleAdvExtraIncome()
    {
    
        // Логваме се
        $browser = $this->SetUp();
    
        //Отваряме папката на фирмата
        $browser = $this->SetFirm();
        
        // нова продажба - проверка има ли бутон
        if(strpos($browser->gettext(), 'Продажба')) {
            $browser->press('Продажба');
        } else {
            $browser->press('Нов...');
            $browser->press('Продажба');
        }
         
        //$browser->hasText('Създаване на продажба');
        $browser->setValue('reff', 'MinkP');
        $browser->setValue('bankAccountId', '');
        $browser->setValue('note', 'MinkPAdvExtraIncome');
        $browser->setValue('paymentMethodId', "30% авансово и 70% преди експедиция");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
         
        // Записваме черновата на продажбата
        $browser->press('Чернова');
    
        // Добавяме артикул
        $browser->press('Артикул');
        $browser->setValue('productId', 'Чувал голям 50 L');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '100');
        $browser->setValue('packPrice', '0,32');
    
        // Записваме артикула
        $browser->press('Запис');
        // активираме продажбата
        $browser->press('Активиране');
        //$browser->press('Активиране/Контиране');
         
        if(strpos($browser->gettext(), 'Авансово: BGN 11,52')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешно авансово плащане', 'warning');
        }
    
        if(strpos($browser->gettext(), 'Тридесет и осем BGN и 0,40')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна обща сума', 'warning');
        }
         
        // Проформа
        $browser->press('Проформа');
        $browser->setValue('amountAccrued', '11,52');
        $browser->press('Чернова');
        $browser->press('Активиране');
         
        // ПБД
        $browser->press('ПБД');
        $browser->setValue('ownAccount', '#BG11CREX92603114548401');
        $browser->setValue('amountDeal', '11,52');
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        // Фактура
        $browser->press('Фактура');
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        if(strpos($browser->gettext(), 'Единадесет BGN и 0,52')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна сума във фактурата за аванс', 'warning');
        }
         
        // ПБД
        $browser->press('ПБД');
        $browser->setValue('ownAccount', '#BG11CREX92603114548401');
        $browser->setValue('amountDeal', '38.40');
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        // експедиционно нареждане
        $browser->press('Експедиране');
        $browser->setValue('storeId', 'Склад 1');
        $browser->press('Чернова');
        $browser->press('Контиране');
         
        // Фактура
        $browser->press('Фактура');
        $browser->press('Чернова');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), '-9,60')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна сума за приспадане', 'warning');
        }
         
        // Приключване
        $browser->press('Приключване');
        $browser->setValue('valiorStrategy', 'Най-голям вальор в нишката');
        $browser->press('Чернова');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), '38,40 38,40 49,92 38,40')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешни суми в мастера', 'warning');
        }
        //Проверка изв.приход
        if(strpos($browser->gettext(), 'BGN 11,52 BGN 0,00')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна сума - извънреден приход', 'warning');
        }
       
    }
    
    /**
     * 21. Проверка извънредни приходи - валута
     * Продажба - схема с авансово плащане, освободено от ДДС
     * Втората фактура е без приспадане на аванса
     */
     
    //http://localhost/unit_MinkPSales/CreateSaleAdvExtraIncome1/
    function act_CreateSaleAdvExtraIncome1()
    {
    
        // Логваме се
        $browser = $this->SetUp();
    
        //Отваряме папката на фирмата
        $browser = $this->SetFirmEUR();
    
        // нова продажба - проверка има ли бутон
        if(strpos($browser->gettext(), 'Продажба')) {
            $browser->press('Продажба');
        } else {
            $browser->press('Нов...');
            $browser->press('Продажба');
        }
         
        //$browser->hasText('Създаване на продажба');
        $browser->setValue('reff', 'MinkP');
        $browser->setValue('bankAccountId', '');
        $browser->setValue('note', 'MinkPAdvExtraIncome1');
        $browser->setValue('paymentMethodId', "30% авансово и 70% преди експедиция");
        $browser->setValue('chargeVat', "exempt");
         
        // Записваме черновата на продажбата
        $browser->press('Чернова');
    
        // Добавяме нов артикул
        $browser->press('Артикул');
        $browser->setValue('productId', 'Чувал голям 50 L');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '100');
        $browser->setValue('packPrice', '0,32');
    
        // Записваме артикула
        $browser->press('Запис');
        // активираме продажбата
        $browser->press('Активиране');
        //$browser->press('Активиране/Контиране');
         
        if(strpos($browser->gettext(), 'Downpayment: EUR 9,60 ')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешно авансово плащане', 'warning');
        }
    
        if(strpos($browser->gettext(), 'Thirty-two EUR')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна обща сума', 'warning');
        }
         
        // Проформа
        $browser->press('Проформа');
        $browser->setValue('amountAccrued', '9.60');
        $browser->press('Чернова');
        $browser->press('Активиране');
         
        // ПБД
        $browser->press('ПБД');
        $browser->setValue('ownAccount', '#BG11CREX92603114548401');
        $browser->setValue('amountDeal', '9,60');
        //$browser->setValue('amount', '18,78');// - дава грешка
        $browser->press('Чернова');
        $browser->press('Контиране');
        
        // Фактура
        $browser->press('Фактура');
        $browser->setValue('amountAccrued', '9.60');
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        if(strpos($browser->gettext(), 'Tax base: BGN 18,78')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна данъчна основа във фактурата за аванс', 'warning');
        }
         
        // ПБД
        $browser->press('ПБД');
        $browser->setValue('ownAccount', '#BG11CREX92603114548401');
        $browser->setValue('amountDeal', '32.00');
        $browser->setValue('amount', '62,59');
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        // експедиционно нареждане
        $browser->press('Експедиране');
        $browser->setValue('storeId', 'Склад 1');
        $browser->press('Чернова');
        $browser->press('Контиране');
         
        // Фактура
        $browser->press('Фактура');
        $browser->setValue('amountDeducted', '');
        $browser->press('Чернова');
        $browser->press('Контиране');
        
        // Приключване
        $browser->press('Приключване');
        $browser->setValue('valiorStrategy', 'Най-голям вальор в нишката');
        $browser->press('Чернова');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), '32,00 32,00 41,60 41,60')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешни суми в мастера', 'warning');
        }
        //Проверка изв.приход
        if(strpos($browser->gettext(), 'BGN 18,78 BGN 0,00')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна сума - извънреден приход', 'warning');
        }
        
    }
    
    /**
     * 22. Проверка извънредни разходи
     * Продажба - Включено ДДС в цените
     */
     
    //http://localhost/unit_MinkPSales/CreateSaleExtraExpenses/
    function act_CreateSaleExtraExpenses()
    {
    
        // Логваме се
        $browser = $this->SetUp();
    
        //Отваряме папката на фирмата
        $browser = $this->SetFirm();
    
        // нова продажба - проверка има ли бутон
        if(strpos($browser->gettext(), 'Продажба')) {
            $browser->press('Продажба');
        } else {
            $browser->press('Нов...');
            $browser->press('Продажба');
        }
         
        //$browser->hasText('Създаване на продажба');
        $browser->setValue('reff', 'MinkP');
        $browser->setValue('bankAccountId', '');
        $browser->setValue('note', 'MinkPExtraExpenses');
        $browser->setValue('paymentMethodId', "До 3 дни след фактуриране");
        $browser->setValue('chargeVat', "Включено ДДС в цените");
         
        // Записваме черновата на продажбата
        $browser->press('Чернова');
    
        // Добавяме артикул
        $browser->press('Артикул');
        $browser->setValue('productId', 'Чувал голям 50 L');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '100');
        $browser->setValue('packPrice', '0,736');
    
        // Записваме артикула
        $browser->press('Запис');
        // активираме продажбата
        $browser->press('Активиране');
        $browser->press('Активиране/Контиране');
         
        if(strpos($browser->gettext(), 'Седемдесет и три BGN и 0,60')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна обща сума', 'warning');
        }
         
        // експедиционно нареждане
        //$browser->press('Експедиране');
        //$browser->setValue('storeId', 'Склад 1');
        //$browser->press('Чернова');
        //$browser->press('Контиране');
    
        // Фактура
        $browser->press('Фактура');
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        // ПБД
        $browser->press('ПБД');
        $browser->setValue('ownAccount', '#BG11CREX92603114548401');
        $browser->setValue('amountDeal', '71,14');
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        // Приключване
        $browser->press('Приключване');
        $browser->setValue('valiorStrategy', 'Най-голям вальор в нишката');
        $browser->press('Чернова');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), '73,60 73,60 71,14 73,60')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешни суми в мастера', 'warning');
        }
    
        if(strpos($browser->gettext(), 'BGN 0,00 BGN 2,46')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна сума извънреден разход', 'warning');
        }
        
    }
    
    /**
     * 23. Проверка извънредни разходи (платен е само авансът)
     * Продажба - схема с авансово плащане, отделно ДДС
     */
     
    //http://localhost/unit_MinkPSales/CreateSaleAdvExtraExpenses/
    function act_CreateSaleAdvExtraExpenses()
    {
    
        // Логваме се
        $browser = $this->SetUp();
    
        //Отваряме папката на фирмата
        $browser = $this->SetFirm();
    
        // нова продажба - проверка има ли бутон
        if(strpos($browser->gettext(), 'Продажба')) {
            $browser->press('Продажба');
        } else {
            $browser->press('Нов...');
            $browser->press('Продажба');
        }
         
        //$browser->hasText('Създаване на продажба');
        $browser->setValue('reff', 'MinkP');
        $browser->setValue('bankAccountId', '');
        $browser->setValue('note', 'MinkPAdvExtraExpenses');
        $browser->setValue('paymentMethodId', "30% авансово и 70% преди експедиция");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
         
        // Записваме черновата на продажбата
        $browser->press('Чернова');
    
        // Добавяме нов артикул
        $browser->press('Артикул');
        $browser->setValue('productId', 'Чувал голям 50 L');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '100');
        $browser->setValue('packPrice', '0,32');
    
        // Записваме артикула
        $browser->press('Запис');
        // активираме продажбата
        $browser->press('Активиране');
        //$browser->press('Активиране/Контиране');
         
        if(strpos($browser->gettext(), 'Авансово: BGN 11,52')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешно авансово плащане', 'warning');
        }
    
        if(strpos($browser->gettext(), 'Тридесет и осем BGN и 0,40')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна обща сума', 'warning');
        }
         
        // Проформа
        $browser->press('Проформа');
        $browser->setValue('amountAccrued', '11,52');
        $browser->press('Чернова');
        $browser->press('Активиране');
         
        // ПБД
        $browser->press('ПБД');
        $browser->setValue('ownAccount', '#BG11CREX92603114548401');
        $browser->setValue('amountDeal', '11,52');
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        // Фактура
        $browser->press('Фактура');
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        if(strpos($browser->gettext(), 'Единадесет BGN и 0,52')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна сума във фактурата за аванс', 'warning');
        }
         
        // експедиционно нареждане
        $browser->press('Експедиране');
        $browser->setValue('storeId', 'Склад 1');
        $browser->press('Чернова');
        $browser->press('Контиране');
         
        // Фактура
        $browser->press('Фактура');
        $browser->press('Чернова');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), '-9,60')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна сума за приспадане', 'warning');
        }
         
        // Приключване
        $browser->press('Приключване');
        $browser->setValue('valiorStrategy', 'Най-голям вальор в нишката');
        $browser->press('Чернова');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), '38,40 38,40 11,52 38,40')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешни суми в мастера', 'warning');
        }
        if(strpos($browser->gettext(), '0,00 0,00 0,00 0,00')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешни суми в мастера', 'warning');
        }
        //Проверка изв.разход
        if(strpos($browser->gettext(), 'BGN 0,00 BGN 26,88')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна сума - извънреден разход', 'warning');
        }
      
    }
    
    /**
     * 24. Проверка извънредни разходи
     * Продажба - схема с авансово плащане, отделно ДДС
     * Втората фактура е без приспадане на аванса
     */
     
    //http://localhost/unit_MinkPSales/CreateSaleAdvExtraExpenses1/
    function act_CreateSaleAdvExtraExpenses1()
    {
    
        // Логваме се
        $browser = $this->SetUp();
    
        //Отваряме папката на фирмата
        $browser = $this->SetFirm();
    
        // нова продажба - проверка има ли бутон
        if(strpos($browser->gettext(), 'Продажба')) {
            $browser->press('Продажба');
        } else {
            $browser->press('Нов...');
            $browser->press('Продажба');
        }
         
        //$browser->hasText('Създаване на продажба');
        $browser->setValue('reff', 'MinkP');
        $browser->setValue('bankAccountId', '');
        $browser->setValue('note', 'MinkPAdvExtraExpenses1');
        $browser->setValue('paymentMethodId', "30% авансово и 70% преди експедиция");
        $browser->setValue('chargeVat', "Отделен ред за ДДС");
         
        // Записваме черновата на продажбата
        $browser->press('Чернова');
    
        // Добавяме нов артикул
        $browser->press('Артикул');
        $browser->setValue('productId', 'Чувал голям 50 L');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '100');
        $browser->setValue('packPrice', '0,32');
    
        // Записваме артикула
        $browser->press('Запис');
        // активираме продажбата
        $browser->press('Активиране');
        //$browser->press('Активиране/Контиране');
         
        if(strpos($browser->gettext(), 'Авансово: BGN 11,52')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешно авансово плащане', 'warning');
        }
    
        if(strpos($browser->gettext(), 'Тридесет и осем BGN и 0,40')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна обща сума', 'warning');
        }
         
        // Проформа
        $browser->press('Проформа');
        $browser->setValue('amountAccrued', '11,52');
        $browser->press('Чернова');
        //$browser->setValue('Ignore', 1);
        //$browser->press('Чернова');
        $browser->press('Активиране');
         
        // ПБД
        $browser->press('ПБД');
        $browser->setValue('ownAccount', '#BG11CREX92603114548401');
        $browser->setValue('amountDeal', '11,52');
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        // Фактура
        $browser->press('Фактура');
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        if(strpos($browser->gettext(), 'Единадесет BGN и 0,52')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна сума във фактурата за аванс', 'warning');
        }
         
        // ПБД
        $browser->press('ПБД');
        $browser->setValue('ownAccount', '#BG11CREX92603114548401');
        $browser->setValue('amountDeal', '22.33');
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        // експедиционно нареждане
        $browser->press('Експедиране');
        $browser->setValue('storeId', 'Склад 1');
        $browser->press('Чернова');
        $browser->press('Контиране');
         
        // Фактура
        $browser->press('Фактура');
        $browser->setValue('amountDeducted', '');
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        // Приключване
        $browser->press('Приключване');
        $browser->setValue('valiorStrategy', 'Най-голям вальор в нишката');
        $browser->press('Чернова');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), '38,40 40,32 33,85 49,92')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешни суми в мастера', 'warning');
        }
        //Проверка изв.разход
        if(strpos($browser->gettext(), 'BGN 0,00 BGN 6,47')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна сума - извънреден разход', 'warning');
        }
        
    }
    
    /**
     * 25. Продажба договор за изработка
     * да се добави задание, задача
     *
     */
     
    //http://localhost/unit_MinkPSales/CreateSaleManuf/
    function act_CreateSaleManuf()
    {
        // Логване
        $browser = $this->SetUp();
    
        //Отваряме папката на фирмата
        $browser = $this->SetFirmEUR();
    
        // нова продажба - проверка има ли бутон
        if(strpos($browser->gettext(), 'Продажба')) {
            $browser->press('Продажба');
        } else {
            $browser->press('Нов...');
            $browser->press('Продажба');
        }
        $enddate=strtotime("+2 Days");
        $browser->setValue('reff', 'MinkP');
        $browser->setValue('bankAccountId', '');
        $browser->setValue('note', 'MinkPSaleManuf');
        $browser->setValue('paymentMethodId', "До 3 дни след фактуриране");
        $browser->setValue('chargeVat', 'exempt');
        //$browser->setValue('chargeVat', "Oсвободено от ДДС");//Ако контрагентът е от България дава грешка 234 - NodeElement.php
        $browser->setValue('template', 'Manufacturing contract');
        // Записване черновата на продажбата
        $browser->press('Чернова');
    
        // Добавяне на артикул
        $browser->press('Артикул');
        $browser->setValue('productId', 'Артикул по запитване');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '500');
        $browser->setValue('packPrice', '0.51');
         
        // Записване на артикула
        $browser->press('Запис');
         
        // активиране на продажбата
        $browser->press('Активиране');
        $browser->press('Активиране/Контиране');
         
        if(strpos($browser->gettext(), 'Two hundred and fifty-five EUR')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна обща сума', 'warning');
        }
        // експедиционно нареждане
        //$browser->press('Експедиране');
        //$browser->setValue('storeId', 'Склад 1');
        //$browser->setValue('template', 'Packaging list');
        //$browser->press('Чернова');
        //$browser->press('Контиране');
    
        // Фактура
        $browser->press('Фактура');
        $browser->setValue('vatReason', 'чл.53 от ЗДДС – ВОД');
        $browser->press('Чернова');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Tax base: BGN 498,74')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна данъчна основа във фактурата', 'warning');
        }
        
    }
    
    /**
     * 26. Продажба - договор за услуга
     */
     
    //http://localhost/unit_MinkPSales/CreateSaleService/
    function act_CreateSaleService()
    {
    
        // Логване
        $browser = $this->SetUp();
    
        //Отваряне папката на фирмата
        $browser = $this->SetFirm();
    
        // нова продажба - проверка има ли бутон
        if(strpos($browser->gettext(), 'Продажба')) {
            $browser->press('Продажба');
        } else {
            $browser->press('Нов...');
            $browser->press('Продажба');
        }
         
        //$browser->hasText('Създаване на продажба');
        $browser->setValue('reff', 'MinkP');
        $browser->setValue('bankAccountId', '');
        $browser->setValue('note', 'MinkPSaleService');
        $browser->setValue('paymentMethodId', "До 3 дни след фактуриране");
        $browser->setValue('chargeVat', "Включено ДДС в цените");
        $browser->setValue('template', 'Договор за услуга');
        // Записване черновата на продажбата
        $browser->press('Чернова');
    
        // Добавяне на артикул - услуга
        $browser->press('Артикул');
        $browser->setValue('productId', 'Транспорт');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', 1);
        $browser->setValue('packPrice', 788.56);
        $browser->setValue('discount', 10);
        // Записване на артикула
        $browser->press('Запис');
        // активиране на продажбата
        $browser->press('Активиране');
        $browser->setValue('action[ship]', 'ship');
        $browser->press('Активиране/Контиране');
        if(strpos($browser->gettext(), 'Отстъпка: BGN 78,86')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна отстъпка', 'warning');
        }
        if(strpos($browser->gettext(), 'Седемстотин и девет BGN и 0,70')) {
            
        } else {
            return unit_MinkPbgERP::reportErr('Грешна обща сума', 'warning');
        }
        
        // Фактура
        $browser->press('Фактура');
        $browser->press('Чернова');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Данъчна основа: BGN 591,42')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна сума във фактура', 'warning');
        }
        //return $browser->getHtml();
    }
    
    /**
     * 27. Продажба - Кредитно известие за цялото количество 
     */
     
    //http://localhost/unit_MinkPSales/CreateCreditInvoice/
    function act_CreateCreditInvoice()
    {
    
        // Логване
        $browser = $this->SetUp();
    
        //Отваряне папката на фирмата
        $browser = $this->SetFirm();
    
        // нова продажба - проверка има ли бутон
        if(strpos($browser->gettext(), 'Продажба')) {
            $browser->press('Продажба');
        } else {
            $browser->press('Нов...');
            $browser->press('Продажба');
        }
         
        //$browser->hasText('Създаване на продажба');
        $browser->setValue('reff', 'MinkP');
        $browser->setValue('bankAccountId', '');
        $browser->setValue('note', 'MinkPSaleCI');
        $browser->setValue('paymentMethodId', "До 3 дни след фактуриране");
        $browser->setValue('chargeVat', "Включено ДДС в цените");
        // Записване черновата на продажбата
        $browser->press('Чернова');
    
        // Добавяне на артикул
        $browser->press('Артикул');
        $browser->setValue('productId', 'Други стоки');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '40');
        $browser->setValue('packPrice', '+3.1456*0.8-01,00');//1,51648
        $browser->setValue('discount', 3);
        $browser->press('Запис и Нов');
        // Записваме артикула и добавяме нов
        $browser->setValue('productId', 'Чувал голям 50 L');
        $browser->refresh('Запис');
        $browser->setValue('packQuantity', '08.0');
        $browser->setValue('packPrice', '2,13');
        $browser->setValue('discount', 7);
        // Записване на артикула
        $browser->press('Запис');
    
        // активиране на продажбата
        $browser->press('Активиране');
        $browser->press('Активиране/Контиране');
         
        if(strpos($browser->gettext(), 'Отстъпка: BGN 3,01')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна отстъпка', 'warning');
        }
        if(strpos($browser->gettext(), 'Седемдесет и четири BGN и 0,69')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна обща сума', 'warning');
        }
    
        // експедиционно нареждане
        //$browser->press('Експедиране');
        //$browser->setValue('storeId', 'Склад 1');
        //$browser->setValue('template', 'Експедиционно нареждане с цени');
        //$browser->press('Чернова');
        //$browser->press('Контиране');
         
        // Фактура
        $browser->press('Фактура');
        $browser->press('Чернова');
        $browser->press('Контиране');
    
        // Кредитно известие за цялото количество
        $browser->press('Известие');
        $browser->press('Чернова');
        //$browser->click('Редактиране на артикул');
        $browser->click('edt44');
        $browser->setValue('quantity', '0');
        $browser->press('Следващ');
        $browser->setValue('quantity', '0');
        $browser->press('Запис');
        $browser->press('Контиране');
        if(strpos($browser->gettext(), 'Минус седемдесет и четири BGN и 0,69')) {
        } else {
            return unit_MinkPbgERP::reportErr('Грешна сума в КИ за цялото количество', 'warning');
        }
                       
    }
    
    
}