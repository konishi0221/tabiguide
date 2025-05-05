<?php
/* GPT tool 一覧を共有定数として切り出し */
class ToolDefinitions
{
    public const TOOLS = [
        [
            'type' => 'function',
            'function' => [
                'name'        => 'getInfo',
                'description' => '施設 DB から追加情報を取得',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => [
                        'slot' => [
                            'type' => 'string',
                            'enum' => [
                                'location','stay','rule',
                                'amenity','service','contact','nearby_stores'
                            ]
                        ]
                    ],
                    'required' => ['slot']
                ]
            ]
        ],
        [
            'type' => 'function',
            'function' => [
                'name'        => 'searchFAQ',
                'description' => 'FAQ から関連 Q&A を取得',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => [
                        'keywords' => ['type' => 'string']
                    ],
                    'required' => ['keywords']
                ]
            ]
        ],
        [
            'type' => 'function',
            'function' => [
                'name'        => 'saveUnknown',
                'description' => '未知の質問を保存',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => [
                        'question' => ['type' => 'string'],
                        'tag'      => ['type' => 'string']
                    ],
                    'required' => ['question','tag']
                ]
            ]
        ],
        [
            'type' => 'function',
            'function' => [
                'name'        => 'notifyStaff',
                'description' => 'スタッフ依頼を送信',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => [
                        'task'       => ['type' => 'string'],
                        'detail'     => ['type' => 'string'],
                        'room_name'  => ['type' => 'string'],
                        'urgency'    => ['type' => 'string','enum'=>['low','mid','high']],
                        'importance' => ['type' => 'string','enum'=>['low','mid','high']]
                    ],
                    'required' => ['task','detail','room_name']
                ]
            ]
        ],
        [
            'type' => 'function',
            'function' => [
                'name'        => 'updateCtx',
                'description' => 'ゲスト ctx を更新',
                'parameters'  => [
                    'type'       => 'object',
                    'properties' => [
                        'name'      => ['type' => 'string','maxLength'=>30],
                        'room_name' => ['type' => 'string','maxLength'=>60],
                        'stage'     => ['type' => 'string']
                    ]
                ]
            ]
        ]
    ];
}
