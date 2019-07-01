<?php

namespace TsWink\Classes;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;
use Doctrine\DBAL\Schema\Column;
use Doctrine\DBAL\Schema\Table;
use Doctrine\DBAL\Types\IntegerType;
use Doctrine\DBAL\Types\StringType;

Class TswinkGenerator extends Generator
{
    /** @var TypeSimplifier */
    private $typeSimplifier;

    /** @var Table */
    private $table;

    /** @var string */
    private $destination;

    public function __construct()
    {
        parent::__construct();

        $this->typeSimplifier = new TypeSimplifier;

        if(function_exists('config')) {
            $this->destination = base_path(config('tswink.ts_classes_destination'));
        }
    }

    public function generate()
    {
        /** @var Table $table */
        foreach ($this->tables as $table) {
            $this->table = $table;
            $this->processTable();
        }
    }

    private function processTable()
    {
        $this->writeFile($this->fileName() . ".ts", $this->getClassContent());
    }

    private function fileName()
    {
        return str_singular(kebab_case(camel_case($this->table->getName())));
    }

    private function getClassContent()
    {
        $tsClass = "export default interface {$this->getTableNameForClassFile()} {\n";
        foreach ($this->table->getColumns() as $column) {
            $name = TswinkGenerator::escapeName($column->getName());
            $tsClass .= "\t{$name}: {$this->getSimplifiedType($column)};\n";
        }
        return $tsClass . "}\n";
    }

    private static function escapeName($name){
        if(strpos($name, "-") !== false){
            return "'$name'";
        }

        return $name;
    }

    private function getTableNameForClassFile()
    {
        return ucfirst($this->singularFromTableName());
    }

    private function singularFromTableName()
    {
        return str_singular(camel_case($this->table->getName()));
    }

    private function getSimplifiedType(Column $column)
    {
        return $this->typeSimplifier->simplify($column);
    }

    private function writeFile($fileName, $tsClass)
    {
        if(!file_exists($this->destination)) {
            mkdir($this->destination, 077, true);
        }

        $filePath = "{$this->destination}/$fileName";

        $file = fopen($filePath, "w");
        fwrite($file, $tsClass);
        fclose($file);
    }

    public function getDestination()
    {
        return $this->destination;
    }

    public function setDestination($destination)
    {
        $this->destination = $destination;
    }
}
