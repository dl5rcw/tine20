<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Timetracker
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @version     $Id:JsonTest.php 5576 2008-11-21 17:04:48Z p.schuele@metaways.de $
 * 
 * @todo        add test for contract <-> timeaccount relations
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Timetracker_JsonTest::main');
}

/**
 * Test class for Tinebase_Group
 */
class Timetracker_JsonTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Timetracker_Frontend_Json
     */
    protected $_backend = array();
    
    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
		$suite  = new PHPUnit_Framework_TestSuite('Tine 2.0 Timetracker Json Tests');
        PHPUnit_TextUI_TestRunner::run($suite);
	}

    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        $this->_backend = new Timetracker_Frontend_Json();        
    }

    /**
     * Tears down the fixture
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {        
    }
    
    /**
     * try to add a Timeaccount
     *
     */
    public function testAddTimeaccount()
    {
        $timeaccount = $this->_getTimeaccount();
        $timeaccountData = $this->_backend->saveTimeaccount(Zend_Json::encode($timeaccount->toArray()));
        
        // checks
        $this->assertEquals($timeaccount->description, $timeaccountData['description']);
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $timeaccountData['created_by']);
        $this->assertTrue(is_array($timeaccountData['container_id']));
        $this->assertEquals(Tinebase_Model_Container::TYPE_SHARED, $timeaccountData['container_id']['type']);
        
        // cleanup
        $this->_backend->deleteTimeaccounts($timeaccountData['id']);

        // check if it got deleted
        $this->setExpectedException('Tinebase_Exception_NotFound');
        Timetracker_Controller_Timeaccount::getInstance()->get($timeaccountData['id']);
    }
    
    /**
     * try to get a Timeaccount
     *
     */
    public function testGetTimeaccount()
    {
        $timeaccount = $this->_getTimeaccount();
        $timeaccountData = $this->_backend->saveTimeaccount(Zend_Json::encode($timeaccount->toArray()));
        $timeaccountData = $this->_backend->getTimeaccount($timeaccountData['id']);
        
        // checks
        $this->assertEquals($timeaccount->description, $timeaccountData['description']);
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $timeaccountData['created_by']);
        $this->assertTrue(is_array($timeaccountData['container_id']));
        $this->assertEquals(Tinebase_Model_Container::TYPE_SHARED, $timeaccountData['container_id']['type']);
                        
        // cleanup
        $this->_backend->deleteTimeaccounts($timeaccountData['id']);
    }

    /**
     * try to update a Timeaccount
     *
     */
    public function testUpdateTimeaccount()
    {
        $timeaccount = $this->_getTimeaccount();
        $timeaccountData = $this->_backend->saveTimeaccount(Zend_Json::encode($timeaccount->toArray()));
        
        // update Timeaccount
        $timeaccountData['description'] = "blubbblubb";
        $timeaccountUpdated = $this->_backend->saveTimeaccount(Zend_Json::encode($timeaccountData));
        
        // check
        $this->assertEquals($timeaccountData['id'], $timeaccountUpdated['id']);
        $this->assertEquals($timeaccountData['description'], $timeaccountUpdated['description']);
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $timeaccountUpdated['last_modified_by']);
        
        // cleanup
        $this->_backend->deleteTimeaccounts($timeaccountData['id']);
    }
    
    /**
     * try to get a Timeaccount
     *
     */
    public function testSearchTimeaccounts()
    {
        // create
        $timeaccount = $this->_getTimeaccount();
        $timeaccountData = $this->_backend->saveTimeaccount(Zend_Json::encode($timeaccount->toArray()));
        
        // search & check
        $search = $this->_backend->searchTimeaccounts(Zend_Json::encode($this->_getTimeaccountFilter()), Zend_Json::encode($this->_getPaging()));
        $this->assertEquals($timeaccount->description, $search['results'][0]['description']);
        $this->assertEquals(1, $search['totalcount']);
        
        // cleanup
        $this->_backend->deleteTimeaccounts($timeaccountData['id']);
    }
    
    /**
     * try to add a Timesheet
     *
     */
    public function testAddTimesheet()
    {
        $timesheet = $this->_getTimesheet();
        $timesheetData = $this->_backend->saveTimesheet(Zend_Json::encode($timesheet->toArray()));
        
        // checks
        $this->assertEquals($timesheet->description, $timesheetData['description']);
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $timesheetData['created_by']);
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $timesheetData['account_id']);
        
        // cleanup
        $this->_backend->deleteTimeaccounts($timesheetData['timeaccount_id']);
        
        // check if everything got deleted
        $this->setExpectedException('Tinebase_Exception_NotFound');
        Timetracker_Controller_Timesheet::getInstance()->get($timesheetData['id']);
    }
    
    /**
     * try to add a Timesheet
     *
     */
    public function testAddTimesheetWithCustomFields()
    {
        // create custom field
        $customField = $this->_getCustomField();
        
        // create timesheet and add custom fields
        $timesheetArray = $this->_getTimesheet()->toArray();
        $timesheetArray[$customField->name] = Tinebase_Record_Abstract::generateUID();
        $timesheetData = $this->_backend->saveTimesheet(Zend_Json::encode($timesheetArray));
        
        // checks
        $this->assertGreaterThan(0, count($timesheetData['customfields']));
        $this->assertEquals($timesheetArray[$customField->name], $timesheetData['customfields'][$customField->name]);
        
        // cleanup
        $this->_backend->deleteTimeaccounts($timesheetData['timeaccount_id']);
        Tinebase_Config::getInstance()->deleteCustomField($customField);
    }
    
    /**
     * try to get a Timesheet
     *
     */
    public function testGetTimesheet()
    {
        $timesheet = $this->_getTimesheet();
        $timesheetData = $this->_backend->saveTimesheet(Zend_Json::encode($timesheet->toArray()));
        $timesheetData = $this->_backend->getTimesheet($timesheetData['id']);
        
        // checks
        $this->assertEquals($timesheet->description, $timesheetData['description']);
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $timesheetData['created_by']);
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $timesheetData['account_id']);
                
        // cleanup
        $this->_backend->deleteTimeaccounts($timesheetData['timeaccount_id']);
    }

    /**
     * try to update a Timesheet (with relations)
     *
     */
    public function testUpdateTimesheet()
    {
        $timesheet = $this->_getTimesheet();
        $timesheetData = $this->_backend->saveTimesheet(Zend_Json::encode($timesheet->toArray()));
        
        // update Timesheet
        $timesheetData['description'] = "blubbblubb";
        $timesheetUpdated = $this->_backend->saveTimesheet(Zend_Json::encode($timesheetData));
        
        // check
        $this->assertEquals($timesheetData['id'], $timesheetUpdated['id']);
        $this->assertEquals($timesheetData['description'], $timesheetUpdated['description']);
        $this->assertEquals(Tinebase_Core::getUser()->getId(), $timesheetUpdated['last_modified_by']);
        
        // cleanup
        $this->_backend->deleteTimeaccounts($timesheetData['timeaccount_id']);
    }
    
    /**
     * try to search for Timesheets
     *
     */
    public function testSearchTimesheets()
    {
        // create
        $timesheet = $this->_getTimesheet();
        $timesheetData = $this->_backend->saveTimesheet(Zend_Json::encode($timesheet->toArray()));
        
        // search & check
        $search = $this->_backend->searchTimesheets(Zend_Json::encode($this->_getTimesheetFilter()), Zend_Json::encode($this->_getPaging()));
        $this->assertEquals($timesheet->description, $search['results'][0]['description']);
        $this->assertEquals(1, $search['totalcount']);
        
        // cleanup
        $this->_backend->deleteTimeaccounts($timesheetData['timeaccount_id']);
    }

    /************ protected helper funcs *************/
    
    /**
     * get Timesheet
     *
     * @return Timetracker_Model_Timeaccount
     */
    protected function _getTimeaccount()
    {
        return new Timetracker_Model_Timeaccount(array(
            'title'         => Tinebase_Record_Abstract::generateUID(),
            'description'   => 'blabla',
        ), TRUE);
    }
    
    /**
     * get Timesheet (create timeaccount as well)
     *
     * @return Timetracker_Model_Timesheet
     */
    protected function _getTimesheet()
    {
        $timeaccount = Timetracker_Controller_Timeaccount::getInstance()->create($this->_getTimeaccount());
        
        return new Timetracker_Model_Timesheet(array(
            'account_id'        => Tinebase_Core::getUser()->getId(),
            'timeaccount_id'    => $timeaccount->getId(),
            'description'       => 'blabla',
        ), TRUE);
    }

    /**
     * get custom field record
     *
     * @return Tinebase_Model_CustomField
     */
    protected function _getCustomField()
    {
        $record = new Tinebase_Model_CustomField(array(
            'application_id'    => Tinebase_Application::getInstance()->getApplicationByName('Timetracker')->getId(),
            'name'              => Tinebase_Record_Abstract::generateUID(),
            'label'             => Tinebase_Record_Abstract::generateUID(),        
            'model'             => 'Timetracker_Model_Timesheet',
            'type'              => Tinebase_Record_Abstract::generateUID(),
            'length'            => 10,        
        ));
        
        return Tinebase_Config::getInstance()->addCustomField($record);
    }
    
    /**
     * get paging
     *
     * @return array
     */
    protected function _getPaging()
    {
        return array(
            'start' => 0,
            'limit' => 50,
            'sort' => 'creation_time',
            'dir' => 'ASC',
        );
    }

    /**
     * get Timeaccount filter
     *
     * @return array
     */
    protected function _getTimeaccountFilter()
    {
        return array(
            array(
                'field' => 'query', 
                'operator' => 'contains', 
                'value' => 'blabla'
            ),     
            array(
                'field' => 'containerType', 
                'operator' => 'equals', 
                'value' => Tinebase_Model_Container::TYPE_SHARED
            ),     
        );        
    }
    
    /**
     * get Timesheet filter
     *
     * @return array
     */
    protected function _getTimesheetFilter()
    {
        return array(
            array(
                'field' => 'query', 
                'operator' => 'contains', 
                'value' => 'blabla'
            ),
        );        
    }
}
