# AntiHabit#check_and_update_goal_achievement

`app/models/anti_habit.rb` L172-186

`after_save`コールバック。目標日数の変更検知と達成フラグの更新を行う。

```mermaid
flowchart TD
    A[after_save発火] --> B{goal_daysが<br>変更された?<br>saved_change_to_goal_days?}

    B -- Yes --> C[goal_achievedをfalseにリセット<br>update_column]
    B -- No --> D{goal_daysがnil?}

    C --> D

    D -- Yes --> E[処理終了<br>return]
    D -- No --> F{goal_reached?<br>consecutive_days >= goal_days}

    F -- Yes --> G[goal_achieved = true<br>update_column]
    F -- No --> H[goal_achieved = false<br>update_column]

    G --> I[終了]
    H --> I
    E --> I
```
