# AntiHabit.top_weekly_achievers_with_ranks

`app/models/anti_habit.rb` L32-73

週間達成ランキングアルゴリズム。今週（月曜〜本日）の達成日数をSQLで集計し、公開中の悪習慣をランク付けする。人数に応じて表示するランク数を動的に制御する。

```mermaid
flowchart TD
    A[開始] --> B[今週の期間を算出<br>start_of_week: 今週月曜日<br>end_of_week: 本日 と 日曜日 の早い方]
    B --> C[今週の達成日数をSQLで集計<br>anti_habit_records を期間・habit_idでGROUP BY<br>→ サブクエリ weekly_counts]
    C --> D[公開中のAntiHabitに対してINNER JOIN<br>publicly_visible.joins weekly_counts<br>※ 今週0件のhabitは自動除外]
    D --> E[weekly_days_count の降順でORDER BY<br>→ candidatesクエリ実行]
    E --> F[週間達成日数でグループ化<br>group_by weekly_days_count]
    F --> G[日数の降順でソート<br>sort_by -days]
    G --> H[順位付きデータを作成<br>ranked_data配列]

    H --> I{各グループをループ}
    I --> J[rank: current_rank<br>weekly_days: days<br>anti_habits: 作成日順にソート]
    J --> K[current_rank += グループ内の人数]
    K --> I

    I -- ループ完了 --> L{1位の人数 >= 3?}

    L -- Yes --> M[1位のみ表示<br>ranked_data.take 1]
    L -- No --> N{1位 + 2位の<br>合計人数 >= 3?}

    N -- Yes --> O[上位2つの順位グループを表示<br>ranked_data.take 2]
    N -- No --> P[上位3つの順位グループを表示<br>ranked_data.take 3]

    M --> Q[結果を返す]
    O --> Q
    P --> Q
```

## 旧実装（top_consecutive_achievers_with_ranks）との違い

| 観点 | 旧実装 | 新実装 |
|------|--------|--------|
| 集計対象 | 連続達成日数（全期間） | 今週（月曜〜本日）の達成日数 |
| データ取得 | Ruby側で全件ロード後に `.select` | SQLのINNER JOINで除外 |
| ソート | Rubyで `sort_by` | DBに `ORDER BY` を委譲 |
| 0件除外 | `consecutive_days_achieved > 0` をRubyで評価 | INNER JOINにより自動除外 |
