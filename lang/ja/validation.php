<?php

/*
| Rails 版 (config/locales/ja.yml) の文言に合わせたバリデーションメッセージ
*/

return [
    'required' => ':attributeを入力してください',
    'string' => ':attributeは文字列で入力してください',
    'boolean' => ':attributeの値が不正です',
    'integer' => ':attributeは整数で入力してください',
    'numeric' => ':attributeは数値で入力してください',
    'between' => [
        'numeric' => ':attributeは:min〜:maxの範囲で入力してください',
        'string' => ':attributeは:min〜:max文字で入力してください',
    ],
    'max' => [
        'numeric' => ':attributeは:max以下の値にしてください',
        'string' => ':attributeは:max文字以内で入力してください',
    ],
    'min' => [
        'numeric' => ':attributeは:min以上の値にしてください',
        'string' => ':attributeは:min文字以上で入力してください',
    ],
    'regex' => ':attributeは不正な値です',
    'email' => ':attributeは不正な値です',
    'unique' => ':attributeはすでに存在します',
    'confirmed' => 'パスワード確認とパスワードの入力が一致しません',
    'date_format' => ':attributeの形式が正しくありません',

    'attributes' => [
        'name' => '名前',
        'email' => 'メールアドレス',
        'password' => 'パスワード',
        'password_confirmation' => 'パスワード確認',
        'title' => 'タイトル',
        'description' => '説明',
        'tag_names' => 'タグ',
        'goal_days' => '目標達成日数',
        'is_public' => '公開設定',
        'body' => '応援メッセージ',
        'notification_time' => '通知時刻',
        'notification_hour' => '通知時刻 (時)',
        'notification_minute' => '通知時刻 (分)',
        'notification_enabled' => 'LINE通知',
    ],
];
