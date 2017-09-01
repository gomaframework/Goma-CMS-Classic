<?php
defined("IN_GOMA") OR die();

/**
 * Tests ModelRepository.
 *
 * @package SOREDI
 *
 * @author Goma-Team
 * @copyright 2017 Goma-Team
 *
 * @version 1.0
 */
class ModelRepositoryTest extends GomaUnitTest {
    /**
     * tests model repoistory buildWriter
     */
    public function testbuildWriterWrite() {
        $repository = new ModelRepository();
        $modelWriter = $repository->buildWriter(
            new User(),
            IModelRepository::COMMAND_TYPE_UPDATE,
            false,
            false
        );

        $this->assertFalse($modelWriter->isForceWrite());
        $this->assertEqual(IModelRepository::WRITE_TYPE_PUBLISH, $modelWriter->getWriteType());
        $this->assertEqual($modelWriter->getOldId(), 0);
    }

    /**
     * tests model repoistory buildWriter
     */
    public function testbuildWriterWriteWriteTypeNull() {
        $repository = new ModelRepository();
        $modelWriter = $repository->buildWriter(
            new User(),
            IModelRepository::COMMAND_TYPE_UPDATE,
            false,
            false,
            null
        );

        $this->assertFalse($modelWriter->isForceWrite());
        $this->assertEqual(IModelRepository::WRITE_TYPE_PUBLISH, $modelWriter->getWriteType());
        $this->assertEqual($modelWriter->getOldId(), 0);
    }

    /**
     * tests model repoistory buildWriter
     */
    public function testbuildWriterWriteForceWrite() {
        $repository = new ModelRepository();
        $modelWriter = $repository->buildWriter(
            new User(),
            IModelRepository::COMMAND_TYPE_UPDATE,
            false,
            false,
            null,
            null,
            true
        );

        $this->assertTrue($modelWriter->isForceWrite());
        $this->assertEqual(IModelRepository::WRITE_TYPE_PUBLISH, $modelWriter->getWriteType());
        $this->assertEqual($modelWriter->getOldId(), 0);
    }


    /**
     * tests model repoistory buildWriter
     */
    public function testbuildWriteInsertForceWrite() {
        $repository = new ModelRepository();
        $modelWriter = $repository->buildWriter(
            new User(),
            IModelRepository::COMMAND_TYPE_INSERT,
            false,
            false,
            null,
            null,
            true
        );

        $this->assertTrue($modelWriter->isForceWrite());
        $this->assertEqual(IModelRepository::WRITE_TYPE_PUBLISH, $modelWriter->getWriteType());
        $this->assertEqual($modelWriter->getOldId(), 0);
    }
}
