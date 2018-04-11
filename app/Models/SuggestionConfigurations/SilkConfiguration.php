<?php

namespace App\Models\SuggestionConfigurations;

use App\Project;
use App\Settings;
use Storage;

class SilkConfiguration
{
    public $nodes = [
        "{}Prefixes",
        "{}DataSources",
        "{}Interlinks",
        "{}Outputs"
    ];

    public function prepareProject(Project $project) {
        //create project folder

        Storage::disk("projects")->makeDirectory("project" . $project->id);

        //copy source ontology
        $suffix1 = ($project->source->filetype != 'ntriples' ) ? '.nt' : '';
        $source = file_get_contents($project->source->resource->path() . $suffix1);
        Storage::disk("projects")->put("/project" . $project->id . "/source.nt", $source);

        //copy target ontology
        $suffix2 = ($project->target->filetype != 'ntriples' ) ? '.nt' : '';
        $target = file_get_contents($project->target->resource->path() . $suffix2);
        Storage::disk("projects")->put("/project" . $project->id . "/target.nt", $target);
        //create the config
        $newConfig = $this->reconstruct($project->settings->id);

        Storage::disk("projects")->put("/project" . $project->id . "/project" . $project->id . "_config.xml", $newConfig);
        return 0;
    }

    public function reconstruct($id) {
        $settings = Settings::find($id);
        $settings_xml = file_get_contents($settings->resource->path());
        $new = $this->parseXML($settings_xml);

        $prefixes = $this->getNode($new, $this->nodes[0]);
        $datasources = $this->getNode($new, $this->nodes[1]);
        $linkage = $this->getNode($new, $this->nodes[2]);
        $outputs = $this->getNode($new, $this->nodes[3]);

        $newOutput = $this->createOutput($outputs->first());
        $newDatasource = $this->createDatasource($datasources->first());

        $service = new \Sabre\Xml\Service();
        $xml = $service->write('Silk', [
                $prefixes->first(),
                $newDatasource,
                $linkage->first(),
                $newOutput
            ]
        );
        return $xml;
    }

    public function getNode($collection, $name) {
        $node = collect($collection->where("name", $name));
        return $node;
    }

    public function parseXML($xml) {

        $service = new \Sabre\Xml\Service();
        $result = collect($service->parse($xml));
        return $result;
    }

    public function createOutput($originalOutput) {
        $minConcfidence = $originalOutput["value"][0]["attributes"]["minConfidence"];
        $newOutput = [
            "name" => "Outputs",
            "value" => [
                [
                    "name" => "Output",
                    "value" => [

                        [
                            "name" => "Param",
                            "value" => null,
                            "attributes" => [
                                "name" => "file",
                                "value" => "score.nt"
                            ]
                        ],
                        [
                            "name" => "Param",
                            "value" => null,
                            "attributes" => [
                                "name" => "format",
                                "value" => "N-Triples"
                            ]
                        ]
                    ],
                    "attributes" => [
                        "id" => "score",
                        "type" => "alignment",
                        "minConfidence" => $minConcfidence
                    ]
                ]
            ],
            "attributes" => []
        ];
        return $newOutput;
    }

    public function filenameTemplate($filename) {
        return [ "name" => "{}Param",
            "value" => null,
            "attributes" => [
                "name" => "file",
                "value" => $filename
            ]
        ];
    }

    public function formatTemplate() {
        return [ "name" => "{}Param",
            "value" => null,
            "attributes" => [
                "name" => "format",
                "value" => "N-Triples"
            ]
        ];
    }

    public function createDataset($dataset, $filename) {
        $name = $dataset["name"];
        $file = $this->filenameTemplate($filename);
        $format = $this->formatTemplate();
        $graph = isset($dataset["value"][2]) ? $dataset["value"][2] : null;
        $attributes = $dataset["attributes"];
        return [
            "name" => $name,
            "value" => [
                $file,
                $format,
                $graph
            ],
            "attributes" => $attributes
        ];
    }

    public function createDatasource($originalDataSource) {
        $source = $this->createDataset($originalDataSource["value"][0], "source.nt");
        $target = $this->createDataset($originalDataSource["value"][1], "target.nt");
        return [
            "name" => "DataSources",
            "value" => [
                $source,
                $target,
            ],
            "attributes" => []
        ];
    }

    public function validateSettingsFile(Settings $settings) {

        libxml_use_internal_errors(true);
        $schema = $this->validateSchema($settings->resource->path());
        $validationError = \App\ValidationError::create();
        $validationError->bag = $schema;
        $validationError->setting_id = $settings->id;
        $validationError->save();
        return $validationError;
    }

    public function validateXML($xml) {
        return 1;
    }

    public function validateAlignment(Settings $settings) {
        $xml = file_get_contents($settings->resource->path());
        $parsed = $this->parseXML($xml);
        $linkage = $this->getNode($parsed, $this->nodes[2]);
        $source = $linkage[2]["value"][0]["value"][0]["attributes"]["dataSource"];
        if ($source != "source.nt") {
            return 0;
        }
    }

    public function validateSchema($file) {
        libxml_use_internal_errors(true);
        $xml = new \DOMDocument();
        $errors = [];
        if (!$xml->load($file)) {
            foreach (libxml_get_errors() as $error) {
                array_push($errors, $error);
            }
            libxml_clear_errors();
        }
        $schema = storage_path() . "/app/projects/LinkSpecificationLanguage.xsd";
        $bag = [
            "valid" => $xml->schemaValidate($schema),
            "errors" => $errors
        ];
        return collect($bag);
    }    
}
