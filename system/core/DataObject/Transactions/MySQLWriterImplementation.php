<?php defined("IN_GOMA") OR die();

/**
 * implementation for Database-Writer for MySQL.
 *
 * @package		Goma\DB
 *
 * @author		Goma-Team
 * @license		GNU Lesser General Public License, version 3; see "LICENSE.txt"
 */
class MySQLWriterImplementation implements iDataBaseWriter {

    /**
     * writer.
     *
     * @var ModelWriter
     */
    protected $writer;

    /**
     * sets Writer-Object.
     *
     * @param ModelWriter $writer
     */
    public function setWriter($writer)
    {
        $this->writer = $writer;
    }

    /**
     * writes data to Database.
     * @throws SQLException
     */
    public function write()
    {
        $data = $this->writer->getData();

        $baseClass = $this->model()->baseClass();

        // set new versionid and in addition, if not set, new id
        $this->model()->versionid = $this->insertBaseClassAndGetVersionId($data);

        $manipulation = array();

        $this->writer->callExtending("afterInsertBaseClassAndGetVersionId", $data, $manipulation);

        // generate manipulation for each table.
        if ($dataClasses = ClassInfo::DataClasses($baseClass))
        {
            foreach($dataClasses as $class => $table)
            {
                $this->generateTableManipulation($data, $class, $manipulation, $this->model()->versionid);
            }
        }

        $job = "write";
        $this->writer->callModelExtending("onBeforeManipulate", $manipulation, $job, $this);
        $this->writer->callModelExtending("onBeforeWriteData", $manipulation, $job, $this);

        // fire manipulation to DataBase
        if (SQL::manipulate($manipulation)) {
            $this->updateStateTable();

            $this->checkForAndCleanUpDataTable();
        } else {
            throw new SQLException();
        }
    }

    /**
     * publish.
     * @param bool $alreadyWritten
     * @throws SQLException
     */
    public function publish($alreadyWritten = false)
    {
        $manipulation = array();

        if($alreadyWritten) {
            // upgrade snap_priority if existing
            if(isset(ClassInfo::$database[$this->model()->baseTable]["snap_priority"])) {
                $manipulation[$this->model()->baseTable] = array(
                    "command" => "update",
                    "fields" => array(
                        "snap_priority" => 2
                    ),
                    "where" => array(
                        "id" => $this->model()->versionid
                    )
                );
            }

            // allow extensions to hook in
            $job = "write";
            $this->writer->callModelExtending("onBeforeManipulate", $manipulation, $job, $this);
            $this->writer->callModelExtending("onBeforeWriteData", $manipulation, $job, $this);
        }

        // fire manipulation to DataBase
        if (SQL::manipulate($manipulation)) {
            $this->insertIntoStateTable(array(
                "id"            => $this->recordid(),
                "publishedid"   => $this->model()->versionid,
                "stateid"       => $this->model()->versionid
            ), "update");

            $this->model()->stateid = $this->model()->publishedid = $this->model()->versionid;
        } else {
            throw new SQLException();
        }
    }

    /**
     * updates state table with new record and versionid.
     * @throws SQLException
     */
    protected function updateStateTable() {
        if($this->writer->getWriteType() == ModelRepository::WRITE_TYPE_PUBLISH || !DataObject::Versioned($this->model()->classname)) {
            $this->publish();
        } else {
            $this->insertIntoStateTable(array(
                "id"            => $this->recordid(),
                "stateid"       => $this->model()->versionid
            ), "update");

            $this->model()->stateid = $this->model()->versionid;
        }
    }

    /**
     * clean-up for datatable.
     */
    protected function checkForAndCleanUpDataTable() {
        // HERE CLEAN-UP for non-versioned-tables happens
        // if we don't version this dataobject, we need to delete the old record
        if (!DataObject::Versioned($this->model()->classname) && $this->writer->getOldId() && $this->writer->getCommandType() != ModelRepository::COMMAND_TYPE_INSERT) {
            $manipulation = array(
                $this->model()->BaseClass() => array(
                    "command"	=> "delete",
                    "where" 	=> array(
                        "id" => $this->writer->getOldId()
                    )
                )
            );

            if ($dataClasses = ClassInfo::DataClasses($this->model()->BaseClass()))
            {
                foreach(array_keys($dataClasses) as $class)
                {
                    $manipulation[$class] = array(
                        "command"	=> "delete",
                        "where" 	=> array(
                            "id" => $this->writer->getOldId()
                        )
                    );
                }
            }

            $oldId = $this->writer->getOldId();
            $this->writer->callModelExtending("deleteOldVersions", $manipulation, $oldId);

            SQL::manipulate($manipulation);
        }
    }

    /**
     * it forces to have the basic record in database.
     * inserts data into table of base-class and gets new generated versionid back.@global
     *
     * @param array $data
     * @return int
     * @throws SQLException
     */
    protected function insertBaseClassAndGetVersionId($data) {

        // force record in state table.
        $this->forceRecordId();

        // generate the write-manipulation
        $manipulation = array();

        $this->generateTableManipulation($data, $this->model()->BaseClass(), $manipulation);

        if(!SQL::manipulate($manipulation)) {
            throw new LogicException("Manipulation malformed. " . print_r($manipulation, true));
        }

        $id = SQL::Insert_ID();
        if($id == 0) {
            throw new LogicException("No ID was inserted to we have a problem.");
        }

        return $id;
    }

    /**
     * generates manipulation for given ModelClass with given data.
     *
     * @param array $data
     * @param string $class
     * @param array $manipulation to edit
     * @param int $versionId when set to 0 new record is generated
     */
    protected function generateTableManipulation($data, $class, &$manipulation, $versionId = 0) {

        $fields = array_merge(
            $this->generateDefaultTableManipulation($class),
            DataBaseFieldManager::getFieldValues(
                $class,
                $data,
                $this->writer->getCommandType() == ModelRepository::COMMAND_TYPE_INSERT,
                !$this->writer->getSilent()
            )
        );

        if($versionId != 0) {
            $manipulation[$class . "_clean"] = array(
                "command"	=> "delete",
                "table_name"=> ClassInfo::$class_info[$class]["table"],
                "id"		=> $versionId
            );

            $fields["id"] = $versionId;
        }

        $manipulation[$class] = array(
            "command"	=> "insert",
            "fields"	=> $fields
        );
    }

    /**
     * returns default manipulation fields for generated table.
     *
     * @param string $class
     * @return array
     */
    protected function generateDefaultTableManipulation($class) {
        if(ClassManifest::isSameClass($this->model()->BaseClass(), $class)) {
            return array(
                "class_name"	=> $this->model()->classname,
                "last_modified" => NOW,
                "recordid"      => $this->model()->id
            );
        }

        return array();
    }

    /**
     * validates write.
     * throws exception when having problems.
     */
    public function validate() {
        if (!defined("CLASS_INFO_LOADED")) {
            throw new LogicException("Calling ModelWriter::write without loaded ClassInfo is not allowed.");
        }

        if(!is_object($this->writer->getModel())) {
            throw new InvalidArgumentException("Model must be a DataObject.");
        }

        ModelBuilder::checkForTableExisting($this->writer->getModel());
    }


    /**
     * tries to find recordid in versions of state-table.
     *
     * @param int $recordid
     * @return Tuple<publishedid, stateid>
     * @throws SQLException
     */
    public function findStateRow($recordid) {
        $query = new SelectQuery($this->model()->BaseTable() . "_state", array("publishedid", "stateid"), array("id" => $recordid));
        if ($query->execute()) {
            if($row = $query->fetch_object()) {
                return new Tuple($row->publishedid, $row->stateid);
            } else {
                return new Tuple(0, 0);
            }
        } else {
            throw new MySQLException();
        }
    }

    /**
     * returns recordid of model.
     */
    protected function recordid() {
        return $this->model()->id;
    }

    /**
     * forces recordid is represented in state-table.
     * it may change recordid.
     * @throws SQLException
     */
    protected function forceRecordId() {
        if ($this->writer->getCommandType() == ModelRepository::COMMAND_TYPE_INSERT) {
            $this->insertIntoStateTable(array(
                "stateid" => 0,
                "publishedid" => 0
            ));

            $id = sql::insert_id();
            if($id == 0) {
                throw new LogicException("There must be an inserted id.");
            }

            $this->model()->id = $id;
        } else if (!isset($data["publishedid"])) {
            $query = new SelectQuery($this->model()->baseTable() . "_state", array("id"), array("id" => $this->recordid()));
            if ($query->execute()) {
                $data = $query->fetch_assoc();

                // check if record was found.
                if (!isset($data["id"])) {
                    $this->insertIntoStateTable(array(
                        "id" => $this->recordid()
                    ));
                }

            } else {
                throw new SQLException("Could not check for recordid in Table " . $this->model()->baseTable . "_state.");
            }
        }
    }

    /**
     * inserts or updates data in state-table.
     *
     * @param array $fields
     * @param string $command
     * @throws SQLException
     */
    protected function insertIntoStateTable($fields, $command = "insert") {
        $manipulation = array(
            "state" => array(
                "table_name"=> $this->writer->getModel()->baseTable . "_state",
                "command"	=> $command,
                "fields"	=> $fields
            )
        );

        if($command == "update") {
            if(isset($fields["id"])) {
                $manipulation["state"]["id"] = $fields["id"];
            } else {
                throw new LogicException("Updating State-Table requires an ID.");
            }
        }

        $job = "write_state";
        $this->writer->callModelExtending("onBeforeManipulate", $manipulation, $job);

        if(!SQL::manipulate($manipulation)) {
            throw new SQLException("Could not insert into state table.");
        }
    }

    /**
     * returns model.
     *
     * @return DataObject
     */
    protected function model() {
        return $this->writer->getModel();
    }
}
