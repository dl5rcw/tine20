<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Group
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2013-2013 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

/**
 * Test class for Tinebase_Group_ActiveDirectory
 */
class Tinebase_User_ActiveDirectoryTest extends PHPUnit_Framework_TestCase
{
    protected $baseDN     = 'dc=example,dc=com';
    protected $groupsDN   = 'cn=users,dc=example,dc=com';
    protected $userDN     = 'cn=users,dc=example,dc=com';
    protected $domainSid  = 'S-1-5-21-2127521184-1604012920-1887927527';
    protected $userSid    = 'S-1-5-21-2127521184-1604012920-1887927527-72713';
    protected $groupSid   = 'S-1-5-21-2127521184-1604012920-1887927527-62713';
    protected $groupObjectGUID = '2127521184-1604012920-1787927527';
    protected $userObjectGUID  = '2127521184-1604012920-1887927527';
    protected $groupBaseFilter = 'objectclass=group';
    protected $userBaseFilter  = 'objectclass=user';
    
    /**
     * Sets up the fixture.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
        $this->markTestIncomplete('group backend breaks mocking');
        
        $this->_userAD = new Tinebase_User_ActiveDirectory(array(
            'userDn'   => $this->userDN,
            'groupsDn' => $this->groupsDN,
            'ldap'     => $this->_getTinebaseLdapStub(),
            'useRfc2307' => true
        )); 
    }

    /**
     * try to add a group
     *
     */
    public function testAddUserToSyncBackend()
    {
        $addedUser = $this->_userAD->addUserToSyncBackend(new Tinebase_Model_FullUser(array(
            'accountLoginName'    => 'larskneschke',
            'accountPrimaryGroup' => $this->groupSid,
            'accountDisplayName'  => 'Kneschke, Lars',
            'accountLastName'     => 'Kneschke',
            'accountFullName'     => 'Lars Kneschke'
        )));
        
        #$this->assertEquals(62713, $primaryGroupNumber);
    }
    
    /**
     * 
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    protected function _getTinebaseLdapStub()
    {
        $stub = $this->getMockBuilder('Tinebase_Ldap')
                     ->disableOriginalConstructor()
                     ->getMock();
        
        $stub->expects($this->any())
             ->method('getFirstNamingContext')
             ->will($this->returnValue($this->baseDN));
        
        $stub->expects($this->any())
             ->method('search')
             ->will($this->returnCallback(array($this, '_stubSearchCallback')));
        
        $stub->expects($this->any())
             ->method('getEntry')
             ->will($this->returnCallback(array($this, '_stubGetEntryCallback')));
        
        return $stub;
    }
    
    public function _stubGetEntryCallback($dn, array $attributes = array(), $throwOnNotFound = false)
    {
        switch ($dn) {
            default:
                $this->fail("unkown dn $filter in " . __METHOD__);
                
                break;
        }
    }
    
    public function _stubSearchCallback($filter, $basedn = null, $scope = self::SEARCH_SCOPE_SUB, array $attributes = array(), $sort = null, $collectionClass = null)
    {
        switch ((string) $filter) {
            case 'objectClass=domain':
                return $this->_getZendLdapCollectionStub(array('objectsid' => array($this->domainSid)));
                
                break;
                
            case "(&(objectclass=group)(objectguid=$this->groupObjectGUID))":
                return $this->_getZendLdapCollectionStub(array('objectsid' => array($this->groupSid)));
                
                break;
                
            case '(&(objectclass=user))':
                return $this->_getZendLdapCollectionStub(array(
                    'objectguid'     => array($this->userObjectGUID),
                    'cn'             => 'Kneschke, Lars',
                    'givenname'      => 'Kneschke',
                    'sn'             => 'Lars',
                    'samaccountname' => 'larskneschke',
                    'primarygroupid' => 513
                ));
                break;
                
            default:
                $this->fail("unkown filter $filter in " . __METHOD__);
                
                break;
        }
    }
    
    protected function _getZendLdapCollectionStub($data)
    {
        $stub = $this->getMockBuilder('Zend_Ldap_Collection')
             ->disableOriginalConstructor()
             ->getMock();
        
        $stub->expects($this->any())
             ->method('getFirst')
             ->will($this->returnValue($data));
        
        return $stub;
    }
}
