<?php
/**
 * Tine 2.0
 *
 * @package     Setup
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id: Mysql.php 2335 2008-05-07 14:07:23Z metagreiling $
 *
 */

/**
 * setup backend class for MySQL 5.0 +
 *
 * @package     Setup
 */
class Setup_Backend_Oracle extends Setup_Backend_Abstract
{
    protected $_config = '';
    
    protected $_table ='';
   
    protected $_autoincrementID = '';
   
    public function __construct()
    {
        $this->_config = Zend_Registry::get('configFile');
    }
    
    /**
     * takes the xml stream and creates a table
     *
     * @param object $_table xml stream
     */
    public function createTable(Setup_Backend_Schema_Table_Abstract  $_table)
    {
        $this->_table = $_table->name;
        $statement = $this->getCreateStatement($_table);
        echo "<hr color=green>";

     //   echo $statement;
        
        $this->execQueryVoid($statement);
        
        if (!empty($this->_autoincrementId)) {
            $statement = $this->getIncrementSequence($_table->name);
            $this->execQueryVoid($statement);
            $statement = $this->getIncrementTrigger($_table->name);
            $this->execQueryVoid($statement);
            
     //       echo $statement;
            unset($this->_autoincrementId);
        }
        echo "<hr color=red>";

            
    }
    
    public function getIncrementSequence($_tableName) 
    { 

        $statement = '    CREATE SEQUENCE "' . SQL_TABLE_PREFIX . substr($_tableName, 0, 20) . '_seq" 
                        MINVALUE 1
                        MAXVALUE 999999999999999999999999999 
                        INCREMENT BY 1
                        START WITH 1 
                        NOCACHE  
                        NOORDER  
                        NOCYCLE
                        ';
            
        return $statement;
    }
                
    public function getIncrementTrigger($_tableName) 
    {
        $statement = '                    
                        CREATE TRIGGER "' . SQL_TABLE_PREFIX .  substr($_tableName, 0, 20) . '_tri"
                        BEFORE INSERT ON "' .  SQL_TABLE_PREFIX . $_tableName . '"
                        FOR EACH ROW
                        BEGIN
                        SELECT "' . SQL_TABLE_PREFIX .  substr($_tableName, 0, 20) . '_seq".NEXTVAL INTO :NEW."' . $this->_autoincrementId .'" FROM DUAL;
                        END;
                        ';
    
        return $statement;
    }
    
    
    
    public function getCreateStatement(Setup_Backend_Schema_Table_Abstract  $_table)
    {
        $statement = 'CREATE TABLE "' . SQL_TABLE_PREFIX . $_table->name . "\" (\n";
        $statementSnippets = array();
     
        foreach ($_table->fields as $field) {
            if (isset($field->name)) {
               $statementSnippets[] = $this->getFieldDeclarations($field);
            }
        }

        foreach ($_table->indices as $index) {
        
            if ($index->foreign) {
               $statementSnippets[] = $this->getForeignKeyDeclarations($index);
            } else if ($index->primary || $index->unique) {
               $statementSnippets[] = $this->getIndexDeclarations($index);
            }
        }
        $statement .= implode(",\n", $statementSnippets) . "\n)";
        echo "<pre>$statement</pre>";
        return $statement;
    
    }
    
    
    /**
     * checks if application is installed at all
     *
     * @param unknown_type $_application
     * @return unknown
     */
    public function applicationExists($_application)
    {
        if ($this->tableExists('applications')) {
            if ($this->applicationVersionQuery($_application) != false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * check's if a given table exists
     *
     * @param string $_tableSchema
     * @param string $_tableName
     * @return boolean return true if the table exists, otherwise false
     */
    public function tableExists($_tableName)
    {
         $select = Zend_Registry::get('dbAdapter')->select()
          ->from('information_schema.tables')
          ->where('TABLE_SCHEMA = ?', $this->_config->database->dbname)
          ->where('TABLE_NAME = ?',  SQL_TABLE_PREFIX . $_tableName);

        try {
            $stmt = $select->query();
            $table = $stmt->fetchObject();
        } catch (Zend_Db_Exception $e){
            return false;
        }
        return true; 
    }
    
    /**
     * check's a given database table version 
     *
     * @param string $_tableName
     * @return boolean return string "version" if the table exists, otherwise false
     */
    
    public function tableVersionQuery($_tableName)
    {
        $select = Zend_Registry::get('dbAdapter')->select()
                ->from( SQL_TABLE_PREFIX . 'application_tables')
                ->where('name = ?', SQL_TABLE_PREFIX . $_tableName);

        try {
            $stmt = $select->query();
            $table = $stmt->fetchAll();
        } catch (Zend_Db_Exception $e){
        
        }
        return $version[0]['version'];
    }
    
    /**
     * check's a given application version
     *
     * @param string $_application
     * @return boolean return string "version" if the table exists, otherwise false
     */
    public function applicationVersionQuery($_application)
    {    
        $select = Zend_Registry::get('dbAdapter')->select()
                ->from( SQL_TABLE_PREFIX . 'applications')
                ->where('name = ?', $_application);

        try {
            $stmt = $select->query();
            $table = $stmt->fetchAll();
        } catch (Zend_Db_Exception $e){
        
        }
        if (empty($version)) {
            return false;
        } else {
            return $version[0]['version'];
        }
    }
    
    public function getExistingSchema($_tableName)
    {
        // Get common table information
         $select = Zend_Registry::get('dbAdapter')->select()
          ->from('information_schema.tables')
          ->where('TABLE_SCHEMA = ?', $this->_config->database->dbname)
          ->where('TABLE_NAME = ?',  SQL_TABLE_PREFIX . $_tableName);
          
          
        $stmt = $select->query();
        $tableInfo = $stmt->fetchObject();
        
        //$existingTable = new Setup_Backend_Schema_Table($tableInfo);
        $existingTable = Setup_Backend_Schema_Table_Factory::factory('Mysql', $tableInfo);
       // get field informations
        $select = Zend_Registry::get('dbAdapter')->select()
          ->from('information_schema.COLUMNS')
          ->where('TABLE_NAME = ?', SQL_TABLE_PREFIX .  $_tableName);

        $stmt = $select->query();
        $tableColumns = $stmt->fetchAll();

        foreach ($tableColumns as $tableColumn) {
            $field = Setup_Backend_Schema_Field_Factory::factory('Mysql', $tableColumn);
            $existingTable->addField($field);
            
            if ($field->primary === 'true' || $field->unique === 'true' || $field->mul === 'true') {
                $index = Setup_Backend_Schema_Index_Factory::factory('Mysql', $tableColumn);
                        
                // get foreign keys
                $select = Zend_Registry::get('dbAdapter')->select()
                  ->from('information_schema.KEY_COLUMN_USAGE')
                  ->where('TABLE_NAME = ?', SQL_TABLE_PREFIX .  $_tableName)
                  ->where('COLUMN_NAME = ?', $tableColumn['COLUMN_NAME']);

                $stmt = $select->query();
                $keyUsage = $stmt->fetchAll();

                foreach ($keyUsage as $keyUse) {
                    if ($keyUse['REFERENCED_TABLE_NAME'] != NULL) {
                        $index->setForeignKey($keyUse);
                    }
                }
                $existingTable->addIndex($index);
            }
        }
        
        //var_dump($existingTable);
        
      
        return $existingTable;
    }
    
    
    public function checkTable(Setup_Backend_Schema_Table_Abstract $_table)
    {
        
        $string = $this->getCreateStatement($_table);
        $dump = $this->execQuery('SHOW CREATE TABLE ' . SQL_TABLE_PREFIX . $_table->name);
        $compareString = preg_replace('/ AUTO_INCREMENT=\d*/', '', $dump[0]['Create Table']);
        
        if ($compareString != $string) {
            echo "XML<br/>" . $string;
            echo "<hr color=red>MYSQL<br/>";
            for ($i = 0 ; $i < (strlen($compareString)+1) ; $i++) {
                if ($compareString[$i] == $string[$i]) {
                    echo $compareString[$i];
                } else {
                    echo "<font color=red>" . $compareString[$i] . "</font>";
                }
            }
            throw new Exception ("<h1>Failure</h1>");
        }
        
        /*
        foreach ($existentTable->fields as $existingFieldKey => $existingField) {
            
            foreach ($_table->fields as $spalte) {
                if ($spalte->name == $existingField->name) {
                
                    if (NULL != (array_diff($spalte->toArray(), $existingField->toArray()))) {
                        
                        print_r("Differences between database and newest xml declarations\n");
                        echo $_table->name . " database: ";
                       // var_dump($existingField);
                        var_dump($existingField->toArray());
                        echo "XML field: ";
                       // var_dump($spalte);
                        var_dump($spalte->toArray());
                        
                    }
                }
            }
        }
        */
    }
    
    /**
    * add table to tine registry
    *
    * @param Tinebase_Model_Application
    * @param string name of table
    * @param int version of table
    * @return int
    */
    public function addTable(Tinebase_Model_Application $_application, $_name, $_version)
    {
//	var_dump($_application);
	
        $applicationTable = new Tinebase_Db_Table(array('name' => SQL_TABLE_PREFIX . 'application_tables'));
        $applicationData = array(
            'application_id'    => $_application->id,
            'name'              =>  SQL_TABLE_PREFIX . $_name,
            'version'           => $_version
        );

        $applicationID = $applicationTable->insert($applicationData);
		if ($applicationID->id != ((int) $applicationID->id )) {
            $applicationID->id = $this->applicationTable->getAdapter()->lastSequenceId(SQL_TABLE_PREFIX . '_applications_seq');
        }
		
		
		
        return $applicationID;
    }
    
    /*
    * execute insert statement for default values (records)
    * handles some special fields, which can't contain static values
    * 
    * @param SimpleXMLElement $_record
    */
    public function execInsertStatement(SimpleXMLElement $_record)
    {
        $table = new Tinebase_Db_Table(array(
           'name' => SQL_TABLE_PREFIX . $_record->table->name
        ));

        foreach ($_record->field as $field) {
            if (isset($field->value['special'])) {
                switch(strtolower($field->value['special'])) {
                    case 'now':
                        $value = Zend_Date::now()->getIso();
                        break;
                    
                    case 'account_id':
                        break;
                    
                    case 'application_id':
                        $application = Tinebase_Application::getInstance()->getApplicationByName($field->value);
                        $value = $application->id;
                        break;
                    
                    default:
                        throw new Exception('unsupported special type ' . strtolower($field->value['special']));
                    
                    }
            } else {
                $value = $field->value;
            }
            // buffer for insert statement
            $data[(string)$field->name] = $value;
        }
        
        // final insert process
        $table->insert($data);
    }

    
    /*
    * removes table from database
    * 
    * @param string tableName
    */
    public function dropTable($_tableName)
    {
        $statement = "DROP TABLE `" . SQL_TABLE_PREFIX . $_tableName . "`;";
        $this->execQueryVoid($statement);
        echo  $_tableName . " geloescht\n";
    }
    
    /*
    * renames table in database
    * 
    * @param string tableName
    */
    public function renameTable($_tableName, $_newName )
    {
        $statement = "ALTER TABLE `" . SQL_TABLE_PREFIX . $_tableName . "` RENAME TO `" . SQL_TABLE_PREFIX . $_newName . "` ;";
        $this->execQueryVoid($statement);
    }
    
    /*
    * add column/field to database table
    * 
    * @param string tableName
    * @param Setup_Backend_Schema_Field declaration
    * @param int position of future column
    */    
    public function addCol($_tableName, Setup_Backend_Schema_Field $_declaration, $_position = NULL)
    {
        $statement = "ALTER TABLE `" . SQL_TABLE_PREFIX . $_tableName . "` ADD COLUMN " ;
        
        $statement .= $this->getFieldDeclarations($_declaration);
        
        if ($_position != NULL) {
            if ($_position == 0) {
                $statement .= ' FIRST ';
            } else {
                $before = $this->execQuery('DESCRIBE `' . SQL_TABLE_PREFIX . $_tableName . '` ');
                $statement .= ' AFTER `' . $before[$_position]['Field'] . '`';
            }
        }
        
        $this->execQueryVoid($statement);
    }
    
    /*
    * rename or redefines column/field in database table
    * 
    * @param string tableName
    * @param Setup_Backend_Schema_Field declaration
    * @param string old column/field name 
    */    
    public function alterCol($_tableName, Setup_Backend_Schema_Field $_declaration, $_oldName = NULL)
    {
        $statement = "ALTER TABLE `" . SQL_TABLE_PREFIX . $_tableName . "` CHANGE COLUMN " ;
        $oldName = $_oldName ;
        
        if ($_oldName == NULL) {
            $oldName = SQL_TABLE_PREFIX . $_declaration->name;
        }
        
        $statement .= " `" . $oldName .  "` " . $this->getFieldDeclarations($_declaration);
        $this->execQueryVoid($statement);    
    }
    
    /*
    * drop column/field in database table
    * 
    * @param string tableName
    * @param string column/field name 
    */    
    public function dropCol($_tableName, $_colName)
    {
        $statement = "ALTER TABLE `" . SQL_TABLE_PREFIX . $_tableName . "` DROP COLUMN `" . $_colName . "`";
        $this->execQueryVoid($statement);    
    }


     /*
    * add a foreign key to database table
    * 
    * @param string tableName
    * @param Setup_Backend_Schema_Index declaration
    */       
    public function addForeignKey($_tableName, Setup_Backend_Schema_Index $_declaration)
    {
        $statement = "ALTER TABLE `" . SQL_TABLE_PREFIX . $_tableName . "` ADD " 
                    . $this->getForeignKeyDeclarations($_declaration);
        $this->execQueryVoid($statement);    
    }

    /*
    * removes a foreign key from database table
    * 
    * @param string tableName
    * @param string foreign key name
    */     
    public function dropForeignKey($_tableName, $_name)
    {
        $statement = "ALTER TABLE `" . SQL_TABLE_PREFIX . $_tableName . "` DROP FOREIGN KEY `" . $_name . "`" ;
        $this->execQueryVoid($statement);    
    }
    
    /*
    * removes a primary key from database table
    * 
    * @param string tableName (there is just one primary key...)
    */         
    public function dropPrimaryKey($_tableName)
    {
        $statement = "ALTER TABLE `" . SQL_TABLE_PREFIX . $_tableName . "` DROP PRIMARY KEY " ;
        $this->execQueryVoid($statement);    
    }
    
    /*
    * add a primary key to database table
    * 
    * @param string tableName 
    * @param Setup_Backend_Schema_Index declaration
    */         
    public function addPrimaryKey($_tableName, Setup_Backend_Schema_Index $_declaration)
    {
        $statement = "ALTER TABLE `" . SQL_TABLE_PREFIX . $_tableName . "` ADD "
                    . $this->getIndexDeclarations($_declaration);
        $this->execQueryVoid($statement);    
    }
 
    /*
    * add a key to database table
    * 
    * @param string tableName 
    * @param Setup_Backend_Schema_Index declaration
    */     
    public function addIndex($_tableName ,  Setup_Backend_Schema_Index$_declaration)
    {
        $statement = "ALTER TABLE `" . SQL_TABLE_PREFIX . $_tableName . "` ADD "
                    . $this->getIndexDeclarations($_declaration);
        $this->execQueryVoid($statement);    
    }
    
    /*
    * removes a key from database table
    * 
    * @param string tableName 
    * @param string key name
    */    
    public function dropIndex($_tableName, $_indexName)
    {
        $statement = "ALTER TABLE `" . SQL_TABLE_PREFIX . $_tableName . "` DROP INDEX `"  . $_indexName. "`" ;
        $this->execQueryVoid($statement);    
    }
    
    /*
    * execute statement without return values
    * 
    * @param string statement
    */    
    public function execQueryVoid($_statement)
    {
        try {
            $stmt = Zend_Registry::get('dbAdapter')->query($_statement);
            //var_dump($stmt);
        } catch (Zend_Db_Exception $e) {
            var_dump($e);
            exit();
        }
    }
    
     /*
    * execute statement  return values
    * 
    * @param string statement
    * @return stdClass object
    */       
    public function execQuery($_statement)
    {
        try {
            $stmt = Zend_Registry::get('dbAdapter')->query($_statement);
        } catch (Zend_Db_Exception $e) {
            var_dump($e);
            return false;
        }
        return $stmt->fetchAll();
    }
    
    /**
     * create the right mysql-statement-snippet for columns/fields
     *
     * @param Setup_Backend_Schema_Field field / column
     * @return string
     */

    public function getFieldDeclarations(Setup_Backend_Schema_Field_Abstract $_field)
    {
        $buffer[] = '  "' . $_field->name . '"';

        switch ($_field->type) {
            case('varchar'): 
                $buffer[] = 'VARCHAR2(' . $_field->length . ')';
                break;
            
            case ('integer'):
                if (isset($_field->length)) {
                    if ($_field->length > 19) {
                        $buffer[] = 'NUMBER(' . $_field->length . ',0)';
                    } else if ($_field->length < 5){
                        $buffer[] = 'NUMBER(1,0)';
                    } else {
                        $buffer[] = 'NUMBER(' . $_field->length . ',0)';
                    }
                } else {
                    $buffer[] = 'NUMBER(11,0)';
                }
                break;
            case ('clob'):
                $buffer[] = 'CLOB';
                break;
                
            case ('longblob'):
            
            case ('blob'):
                $buffer[] = 'BLOB';
                break;
            
            case ('enum'):
                $length = 0;
                foreach ($_field->value as $value) {
                    $values[] = $value;
                    $tempLength = strlen($value);
                    if ($tempLength > $length) {
                        $length = $tempLength;
                    }
                }
                $additional = ''; 
                if (isset($_field->notnull) && $_field->notnull == 'true') {
                    $additional .= ' NOT NULL ';
                }

                if (isset($_field->default)) {
                    if($_field->default != 'NULL') {
                        $buffer[] = Zend_Registry::get('dbAdapter')->quoteInto("default ?", $_field->default) ;
                    } else {
                        $buffer[] = "default NULL" ;
                    }
                }    
                
                $buffer[] = 'VARCHAR2(' . $length . ')' . $additional . ', CONSTRAINT "cons_' . substr($this->_table, 0, 10) . "_" . substr($_field->name, 0, 9) . '_enum" CHECK ("'. $_field->name . "\" IN ('" . implode("','", $values) . "'))";
                break;
            
            case ('datetime'):
                $buffer[] = 'VARCHAR2(25)';
                break;
            
            case ('double'):
                $buffer[] = 'BINARY_DOUBLE';
                break;
            
            case ('float'):
                $buffer[] = 'BINARY_FLOAT';
                break;
            
            case ('decimal'):
                $buffer[] = "NUMBER(" . $_field->value . ")";
                break;
                
                
            case ('text'):
                $buffer[] = 'BLOB';
                break;
                
            default:
                $buffer[] = $_field->type;
        }
        
        if ($_field->type != 'enum') {
            if (isset($_field->notnull) && $_field->notnull == 'true' && !isset($_field->default)) {
                $buffer[] = 'NOT NULL';
            }

            if (isset($_field->default)) {
                if($_field->default != 'NULL') {
                    $buffer[] = Zend_Registry::get('dbAdapter')->quoteInto("default ?", $_field->default) ;
                } else {
                    $buffer[] = "default NULL" ;
                }
            }    
            
        }
        if (isset($_field->autoincrement)) {
            $this->_autoincrementId = $_field->name;
        }
       
        $definition = implode(' ', $buffer);
        return $definition;
    }

    /**
     * create the right mysql-statement-snippet for keys
     *
     * @param Setup_Backend_Schema_Index key
     * @return string
     */
    public function getIndexDeclarations(Setup_Backend_Schema_Index_Abstract $_key)
    {    
        $keys = array();
        if (!empty($_key->primary)) {
            $snippet = "CONSTRAINT \"pk_" . $this->_table . "\" PRIMARY KEY";        
        
        } else if (!empty($_key->unique)) {
            $snippet = "CONSTRAINT \"uni_" . substr($this->_table, 0, 13) . "_" . substr($_key->name, 0, 12) . "\" UNIQUE" ;
        }

        else {
            $snippet = "CONSTRAINT \"idx_" . $this->_table . "_" . $_key->name . "\" INDEX ";
        }
        
        foreach ($_key->field as $keyfield) {
            $key = '"' . (string)$keyfield . '"';
            if (!empty($keyfield->length)) {
                $key .= ' (' . $keyfield->length . ')';
            }
            $keys[] = $key;
        }

        if (empty($keys)) {
            throw new Exception('no keys for index found');
        }

        $snippet .= ' (' . implode(",", $keys) . ')';
        return $snippet;
    }

    /**
     *  create the right mysql-statement-snippet for foreign keys
     *
     * @param object $_key the xml index definition
     * @return string
     */

    public function getForeignKeyDeclarations(Setup_Backend_Schema_Index_Abstract $_key)
    {
        
        $snippet = '  CONSTRAINT "fk_' . substr($this->_table, 0, 13) . "_" . substr($_key->field, 0, 13). '" FOREIGN KEY ';
        $snippet .= '("' . $_key->field . '") REFERENCES "' . SQL_TABLE_PREFIX
                    . $_key->referenceTable  . 
                    '" ("' . $_key->referenceField . '")';

        if (!empty($_key->referenceOnDelete)) {
            $snippet .= " ON DELETE " . strtoupper($_key->referenceOnDelete);
        }
        if (!empty($_key->referenceOnUpdate)) {
            $snippet .= " ON UPDATE " . strtoupper($_key->referenceOnUpdate);
        }
        return $snippet;
    }
}
