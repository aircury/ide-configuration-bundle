<?php declare(strict_types=1);

namespace Aircury\IDEConfiguration\Manipulator;

use Aircury\IDEConfiguration\Model\DatabaseCollection;
use Aircury\Xml\Node;
use Webpatser\Uuid\Uuid;

class DataSourcesManipulator
{
    public function addDatabases(Node $dataSources, DatabaseCollection $databases): void
    {
        $dataSourceManager = $dataSources->getNamedChild('component', ['name' => 'DataSourceManagerImpl']);

        $dataSourceManager['format']          = 'xml';
        $dataSourceManager['multifile-model'] = 'true';

        foreach ($databases->toArray() as $databaseName => $database) {
            $dataSource = $dataSourceManager->getNamedChild('data-source', ['name' => $databaseName]);

            $dataSource['source'] = 'LOCAL';
            $dataSource['uuid']   = $dataSource['uuid'] ?? Uuid::generate(4)->string;

            $database->setId($dataSource['uuid']);

            switch ($database->getDriver()) {
                case 'mysql':
                    $dataSource->getNamedChild('jdbc-driver')->contents = 'com.mysql.jdbc.Driver';
                    break;
                case 'postgresql':
                    $dataSource->getNamedChild('jdbc-driver')->contents = 'org.postgresql.Driver';
                    break;
                default:
                    throw new \RuntimeException('Not implemented: ' . $database->getDriver());
            }

            $dataSource->getNamedChild('synchronize')->contents = 'true';
            $dataSource->getNamedChild('driver-ref')->contents  = $database->getDriver();
            $dataSource->getNamedChild('jdbc-url')->contents    = sprintf(
                'jdbc:%s://%s:%s/%s',
                $database->getDriver(),
                $database->getHost(),
                $database->getPort(),
                $database->getDatabase()
            );
        }
    }
}