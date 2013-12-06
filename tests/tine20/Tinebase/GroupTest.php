<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Group
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2008-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Philipp Schüle <p.schuele@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(__FILE__)) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tinebase_Group
 */
class Tinebase_GroupTest extends TestCase
{
    /**
     * @var array test objects
     */
    protected $objects = array();
    
    /**
     * try to add a group
     * 
     * @return Tinebase_Model_Group
     */
    public function testAddGroup()
    {
        $group = Tinebase_Group::getInstance()->addGroup(new Tinebase_Model_Group(array(
            'name'          => 'tine20phpunit' . Tinebase_Record_Abstract::generateUID(),
            'description'   => 'Group from test testAddGroup'
        )));
        
        $this->_groupsIdsToDelete[] = $group->getId();
        
        $this->assertEquals(Tinebase_Model_Group::VISIBILITY_HIDDEN, $group->visibility);
        
        return $group;
    }
    
    /**
     * try to get all groups containing Managers in their name
     */
    public function testGetGroups()
    {
        $group = $this->testAddGroup();
        
        $groups = Tinebase_Group::getInstance()->getGroups($group->name);
        
        $this->assertEquals(1, count($groups));
    }
    
    /**
     * try to get the group with the name tine20phpunit
     *
     */
    public function testGetGroupByName()
    {
        $group = $this->testAddGroup();
        
        $fetchedGroup = Tinebase_Group::getInstance()->getGroupByName($group->name);
        
        $this->assertEquals($group->name, $fetchedGroup->name);
    }
    
    /**
     * try to get a group by
     *
     */
    public function testGetGroupById()
    {
        $adminGroup = Tinebase_Group::getInstance()->getGroupByName('Administrators');
        
        $group = Tinebase_Group::getInstance()->getGroupById($adminGroup->id);
        
        $this->assertEquals($adminGroup->id, $group->id);
    }
    
    /**
     * try to update a group
     *
     */
    public function testUpdateGroup()
    {
        $testGroup = $this->testAddGroup();
        $testGroup->visibility = 'displayed';
        $testGroup->list_id = null;
        
        $group = Tinebase_Group::getInstance()->updateGroup($testGroup);
        
        $this->assertEquals($testGroup->name, $group->name);
        $this->assertEquals($testGroup->description, $group->description);
        $this->assertEquals('hidden', $group->visibility);
    }
    
    /**
     * try to set/get group members
     * 
     * @return Tinebase_Model_Group
     */
    public function testSetGroupMembers($testGroup = null, $testGroupMembersArray = null)
    {
        $personas = Zend_Registry::get('personas');
        
        if ($testGroup === null) {
            $testGroup = $this->testAddGroup();
        }
        
        if ($testGroupMembersArray === null) {
            $testGroupMembersArray = array($personas['sclever']->accountId, $personas['pwulf']->accountId);
        }
        Tinebase_Group::getInstance()->setGroupMembers($testGroup->getId(), $testGroupMembersArray);
        
        $getGroupMembersArray = Tinebase_Group::getInstance()->getGroupMembers($testGroup->getId());
        
        $this->assertEquals(sort($testGroupMembersArray), sort($getGroupMembersArray));
        
        return $testGroup;
    }

    /**
     * try to add a group member
     */
    public function testAddGroupMember()
    {
        $personas = Zend_Registry::get('personas');
        
        $testGroup = $this->testSetGroupMembers();
        
        Tinebase_Group::getInstance()->addGroupMember($testGroup->getId(), $personas['jmcblack']->accountId);

        $getGroupMembersArray = Tinebase_Group::getInstance()->getGroupMembers($testGroup->getId());
        
        $expectedValues = array($personas['sclever']->accountId, $personas['pwulf']->accountId, $personas['jmcblack']->accountId);
        $this->assertEquals(sort($expectedValues), sort($getGroupMembersArray));
    }
    
    /**
     * try to remove a group member
     */
    public function testRemoveGroupMember()
    {
        $personas = Zend_Registry::get('personas');
        
        $testGroup = $this->testSetGroupMembers();
        
        Tinebase_Group::getInstance()->removeGroupMember($testGroup->getId(), $personas['sclever']->accountId);
        
        $getGroupMembersArray = Tinebase_Group::getInstance()->getGroupMembers($testGroup->getId());
        
        $this->assertEquals(1, count($getGroupMembersArray));
        $expectedValues = array($personas['pwulf']->accountId);
        $this->assertEquals(sort($expectedValues), sort($getGroupMembersArray));
    }

    /**
     * try to delete a group
     */
    public function testDeleteGroup()
    {
        $testGroup = $this->testAddGroup();
        
        Tinebase_Group::getInstance()->deleteGroups($testGroup);
        
        $this->setExpectedException('Tinebase_Exception_Record_NotDefined');
        $group = Tinebase_Group::getInstance()->getGroupById($testGroup);
    }

  /**
     * try to convert group id and check if correct exceptions are thrown 
     */
    public function testConvertGroupIdToInt()
    {
        $this->setExpectedException('Tinebase_Exception_InvalidArgument');
        Tinebase_Model_Group::convertGroupIdToInt (0);
    }

      /**
     * try to convert id of group object and check if correct exceptions are thrown 
     */
    public function testConvertGroupIdToIntWithGroup()
    {
        $this->setExpectedException('Tinebase_Exception_InvalidArgument');
        Tinebase_Model_Group::convertGroupIdToInt(new Tinebase_Model_Group(array(
            'name'          => 'tine20phpunit noid',
            'description'   => 'noid group'
        )));
    }
    
    /**
     * testGetDefaultGroup
     */
    public function testGetDefaultGroup()
    {
        $group = Tinebase_Group::getInstance()->getDefaultGroup();
        $expectedGroupName = Tinebase_User::getBackendConfiguration(Tinebase_User::DEFAULT_USER_GROUP_NAME_KEY);
        $this->assertEquals($expectedGroupName, $group->name);
    }
    
    /**
     * testGetDefaultAdminGroup
     */
    public function testGetDefaultAdminGroup()
    {
        $group = Tinebase_Group::getInstance()->getDefaultAdminGroup();
        $expectedGroupName = Tinebase_User::getBackendConfiguration(Tinebase_User::DEFAULT_ADMIN_GROUP_NAME_KEY);
        $this->assertEquals($expectedGroupName, $group->name);
    }
    
    /**
     * testSetGroupMemberships
     */
    public function testSetGroupMemberships()
    {
        $personas = Zend_Registry::get('personas');
        
        $currentGroupMemberships = $personas['pwulf']->getGroupMemberships();
        
        $newGroupMemberships = current($currentGroupMemberships);
        
        Tinebase_Group::getInstance()->setGroupMemberships($personas['pwulf'], array($newGroupMemberships));
        $newGroupMemberships = $personas['pwulf']->getGroupMemberships();
        
        if (count($currentGroupMemberships) > 1) {
            $this->assertNotEquals($currentGroupMemberships, $newGroupMemberships);
        }
        
        Tinebase_Group::getInstance()->setGroupMemberships($personas['pwulf'], $currentGroupMemberships);
        $newGroupMemberships = $personas['pwulf']->getGroupMemberships();
        
        $this->assertEquals(sort($currentGroupMemberships), sort($newGroupMemberships));
    }
    
    /**
     * testSyncLists
     * 
     * @see 0005768: create addressbook lists when migrating users
     * 
     * @todo make this work for LDAP accounts backend: currently the user is not present in sync backend but in sql
     */
    public function testSyncLists()
    {
        if (Tinebase_Group::getInstance() instanceof Tinebase_Group_Ldap) {
            $this->markTestSkipped('@todo make this work for LDAP accounts backend');
        }
        
        $testGroup = $this->testAddGroup();
        
        // don't use any existing persona here => will break other tests
        $testUser = new Tinebase_Model_FullUser(array(
            'accountLoginName' => Tinebase_Record_Abstract::generateUID(),
            'accountPrimaryGroup' => $testGroup->getId(),
            'accountDisplayName' => Tinebase_Record_Abstract::generateUID(),
            'accountLastName' => Tinebase_Record_Abstract::generateUID(),
            'accountFullName' => Tinebase_Record_Abstract::generateUID(),
            'visibility' => Tinebase_Model_User::VISIBILITY_DISPLAYED
        ));
        $contact = Admin_Controller_User::getInstance()->createOrUpdateContact($testUser);
        $testUser->contact_id = $contact->getId();
        $testUser = Tinebase_User::getInstance()->addUserInSqlBackend($testUser);
        
        Tinebase_User::syncContact($testUser);
        Tinebase_User::getInstance()->updateUserInSqlBackend($testUser);
        
        $this->testSetGroupMembers($testGroup, array($testUser->accountId));
        Tinebase_Group::syncListsOfUserContact(array($testGroup->getId()), $testUser->contact_id);
        $group = Tinebase_Group::getInstance()->getGroupById($testGroup);
        $this->assertTrue(! empty($group->list_id), 'list id empty: ' . print_r($group->toArray(), TRUE));
        
        $list = Addressbook_Controller_List::getInstance()->get($group->list_id);
        $this->assertEquals($group->getId(), $list->group_id);
        $this->assertEquals($group->name, $list->name);
        $this->assertTrue(! empty($list->members), 'list members empty: ' . print_r($list->toArray(), TRUE) 
            . ' should contain: ' . print_r($testUser->toArray(), TRUE));
        $this->assertContains($testUser->contact_id, $list->members);
        
        $appConfigDefaults = Admin_Controller::getInstance()->getConfigSettings();
        $this->assertTrue(! empty($appConfigDefaults), 'app config defaults empty');
        $internal = $appConfigDefaults[Admin_Model_Config::DEFAULTINTERNALADDRESSBOOK];
        $this->assertEquals($internal, $list->container_id, 'did not get correct internal container');
        
        // sync again -> should not change anything
        Tinebase_Group::syncListsOfUserContact(array($group->getId()), $testUser->contact_id);
        $listAgain = Addressbook_Controller_List::getInstance()->get($group->list_id);
        $this->assertEquals($list->toArray(), $listAgain->toArray());
        
        // change list id -> should get list by (group) name
        $group->list_id = NULL;
        $group = Tinebase_Group::getInstance()->updateGroup($group);
        Tinebase_Group::syncListsOfUserContact(array($group->getId()), $testUser->contact_id);
        $this->assertEquals($list->getId(), Tinebase_Group::getInstance()->getGroupById($group)->list_id);
    }
}
