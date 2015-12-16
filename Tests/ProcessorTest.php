<?php
/*
* This file is a part of graphql-youshido project.
*
* @author Alexandr Viniychuk <a@viniychuk.com>
* created: 11/28/15 2:02 AM
*/

namespace Youshido\Tests;

use Youshido\GraphQL\Schema;
use Youshido\GraphQL\Type\Object\ObjectType;
use Youshido\GraphQL\Processor;
use Youshido\GraphQL\Type\TypeMap;
use Youshido\GraphQL\Validator\ResolveValidator\ResolveValidator;
use Youshido\Tests\DataProvider\UserType;

class ProcessorTest extends \PHPUnit_Framework_TestCase
{

    public function testGoogleExtensionQuery()
    {
        $processor = new Processor(new ResolveValidator());
        $processor->setSchema(new Schema());

        $processor->processQuery('
            query IntrospectionQuery {
                __schema {
                    queryType { name }
                    mutationType { name }
                    types {
                        ...FullType
                    }
                    directives {
                        name
                        description
                        args {
                            ...InputValue
                        }
                        onOperation
                        onFragment
                        onField
                    }
                }
            }

            fragment FullType on __Type {
                kind
                name
                description
                fields {
                    name
                    description
                    args {
                        ...InputValue
                    }
                    type {
                        ...TypeRef
                    }
                    isDeprecated
                    deprecationReason
                }
                inputFields {
                    ...InputValue
                }
                interfaces {
                    ...TypeRef
                }
                enumValues {
                    name
                    description
                    isDeprecated
                    deprecationReason
                }
                possibleTypes {
                    ...TypeRef
                }
            }

            fragment InputValue on __InputValue {
                name
                description
                type { ...TypeRef }
                defaultValue
            }

            fragment TypeRef on __Type {
                kind
                name
                ofType {
                    kind
                    name
                    ofType {
                        kind
                        name
                        ofType {
                            kind
                            name
                        }
                    }
                }
            }
        ', []);

        $this->assertTrue(is_array($processor->getResponseData()));
    }

    /**
     * @param $query
     * @param $response
     *
     * @dataProvider predefinedSchemaProvider
     */
    public function testPredefinedQueries($query, $response)
    {
        $schema = new Schema([
            'query' => new ObjectType([
                'name'        => 'TestSchema',
                'description' => 'Root of TestSchema'
            ])
        ]);
        $schema->addQuery('latest',
            new ObjectType(
                [
                    'name'    => 'latest',
                    'args'    => [
                        'id' => ['type' => TypeMap::TYPE_INT]
                    ],
                    'fields'  => [
                        'id'   => ['type' => TypeMap::TYPE_INT],
                        'name' => ['type' => TypeMap::TYPE_STRING]
                    ],
                    'resolve' => function () {
                        return [
                            'id'   => 1,
                            'name' => 'Alex'
                        ];
                    }
                ]),
            [
                'description' => 'latest description',
                'deprecationReason' => 'for test',
                'isDeprecated' => true,
            ]
        );

        $validator = new ResolveValidator();
        $processor = new Processor($validator);

        $processor->setSchema($schema);

        $processor->processQuery($query);

        $this->assertEquals($processor->getResponseData(), $response);
    }

    public function predefinedSchemaProvider()
    {
        return [
            [
                '{ __type { name } }',
                [
                    'errors' => ['Require "name" arguments to query "__type"']
                ]
            ],
            [
                '{ __type (name: "__Type") { name } }',
                [
                    'data' => [
                        '__type' => ['name' => '__Type']
                    ]
                ]
            ],
            [
                '{
                    __schema {
                        types {
                            name,
                            fields {
                                name
                            }
                        }
                    }
                }',
                [
                    'data' => [
                        '__schema' => [
                            'types' => [
                                ['name' => 'latest', 'fields' => [['name' => 'id'], ['name' => 'name']]],
                                ['name' => 'Int', 'fields' => []],
                                ['name' => 'String', 'fields' => []],
                                ['name' => '__Schema', 'fields' => [['name' => 'queryType'], ['name' => 'mutationType'], ['name' => 'types'], ['name' => 'directives']]],
                                ['name' => '__Type', 'fields' => [['name' => 'name'], ['name' => 'kind'], ['name' => 'description'], ['name' => 'ofType'], ['name' => 'inputFields'], ['name' => 'enumValues'], ['name' => 'fields'], ['name' => 'interfaces'], ['name' => 'possibleTypes']]],
                                ['name' => '__InputValue', 'fields' => [['name' => 'name'],['name' => 'description'],['name' => 'type'],['name' => 'defaultValue'],]],
                                ['name' => '__EnumValue', 'fields' => [['name' => 'name'],['name' => 'description'],['name' => 'deprecationReason'],['name' => 'isDeprecated'],]],
                                ['name' => 'Boolean', 'fields' => []],
                                ['name' => '__Field', 'fields' => [['name' => 'name'], ['name' => 'description'], ['name' => 'isDeprecated'], ['name' => 'deprecationReason'], ['name' => 'type'], ['name' => 'args']]],
                                ['name' => '__Argument', 'fields' => [['name' => 'name'], ['name' => 'type'], ['name' => 'description']]],
                                ['name' => '__Interface', 'fields' => [['name' => 'name'], ['name' => 'kind'], ['name' => 'description'], ['name' => 'ofType'], ['name' => 'inputFields'], ['name' => 'enumValues'], ['name' => 'fields'], ['name' => 'interfaces'], ['name' => 'possibleTypes']]],
                                ['name' => '__PossibleOf', 'fields' => [['name' => 'name'], ['name' => 'kind'], ['name' => 'description'], ['name' => 'ofType'], ['name' => 'inputFields'], ['name' => 'enumValues'], ['name' => 'fields'], ['name' => 'interfaces'], ['name' => 'possibleTypes']]],
                                ['name' => '__Directive', 'fields' => [['name' => 'name'], ['name' => 'description'], ['name' => 'args'], ['name' => 'onOperation'], ['name' => 'onFragment'], ['name' => 'onField']]],
                            ]
                        ]
                    ]
                ]
            ],
            [
                '{
                  test : __schema {
                    queryType {
                      kind,
                      name,
                      fields {
                        name,
                        isDeprecated,
                        deprecationReason,
                        description,
                        type {
                          name
                        }
                      }
                    }
                  }
                }',
                ['data' => [
                    'test' => [
                        'queryType' => [
                            'name'   => 'TestSchema',
                            'kind'   => 'OBJECT',
                            'fields' => [
                                ['name' => 'latest', 'isDeprecated' => true, 'deprecationReason' => 'for test', 'description' => 'for test', 'type' => ['name' => 'latest']],
                                ['name' => '__schema', 'isDeprecated' => false, 'deprecationReason' => '', 'description' => '', 'type' => ['name' => '__Schema']],
                                ['name' => '__type', 'isDeprecated' => false, 'deprecationReason' => '', 'description' => '', 'type' => ['name' => '__Type']]
                            ]
                        ]
                    ]
                ]]
            ],
            [
                '{
                  __schema {
                    queryType {
                      kind,
                      name,
                      description,
                      interfaces {
                        name
                      },
                      possibleTypes {
                        name
                      },
                      inputFields {
                        name
                      },
                      ofType{
                        name
                      }
                    }
                  }
                }',
                ['data' => [
                    '__schema' => [
                        'queryType' => [
                            'kind'          => 'OBJECT',
                            'name'          => 'TestSchema',
                            'description'   => 'Root of TestSchema',
                            'interfaces'    => [],
                            'possibleTypes' => [],
                            'inputFields'   => [],
                            'ofType'        => []
                        ]
                    ]
                ]]
            ]
        ];
    }


    /**
     * @dataProvider schemaProvider
     */
    public function testProcessor($query, $response)
    {
        $schema = new Schema();
        $schema->addQuery('latest',
            new ObjectType(
                [
                    'name'    => 'latest',
                    'fields'  => [
                        'id'   => ['type' => TypeMap::TYPE_INT],
                        'name' => ['type' => TypeMap::TYPE_STRING]
                    ],
                    'resolve' => function () {
                        return [
                            'id'   => 1,
                            'name' => 'Alex'
                        ];
                    }
                ]));

        $schema->addQuery('user', new UserType());

        $validator = new ResolveValidator();
        $processor = new Processor($validator);

        $processor->setSchema($schema);
        $processor->processQuery($query);

        $this->assertEquals(
            $processor->getResponseData(),
            $response
        );
    }

    public function schemaProvider()
    {
        return [
            [
                '{ latest { name } }',
                [
                    'data' => ['latest' => null]
                ]
            ],
            [
                '{ user(id:1) { id, name } }',
                [
                    'data' => ['user' => ['id' => 1, 'name' => 'John']]
                ]
            ]
        ];
    }

}
