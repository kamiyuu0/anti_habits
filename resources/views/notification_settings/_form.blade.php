@php
    [$currentHour, $currentMinute] = explode(':', old('notification_time', $setting->localNotificationTime() ?? '00:00'));
    $isNew = ! $setting->exists;
@endphp
<form action="{{ $isNew ? route('anti_habits.notification_setting.store', $antiHabit) : route('anti_habits.notification_setting.update', $antiHabit) }}" method="post" class="space-y-4">
  @csrf
  @unless ($isNew)
    @method('PATCH')
  @endunless

  <!-- エラーメッセージ -->
  @include('shared.form_errors')

  <!-- 通知時刻（5分刻み） -->
  <div class="form-control">
    <div class="label">
      <span class="label-text">通知時刻</span>
    </div>
    <div class="flex gap-2 items-center">
      <select name="notification_hour" id="notification_setting_notification_time_4i">
        @for ($h = 0; $h < 24; $h++)
          @php($value = sprintf('%02d', $h))
          <option value="{{ $value }}" @selected($value === $currentHour)>{{ $value }}</option>
        @endfor
      </select>
      :
      <select name="notification_minute" id="notification_setting_notification_time_5i">
        @for ($m = 0; $m < 60; $m += 5)
          @php($value = sprintf('%02d', $m))
          <option value="{{ $value }}" @selected($value === $currentMinute)>{{ $value }}</option>
        @endfor
      </select>
    </div>
    <div class="label">
      <span class="label-text-alt">5分刻みで選択してください</span>
    </div>
  </div>

  <div class="form-control">
    <label class="label cursor-pointer">
      <input type="hidden" name="notification_enabled" value="0">
      <input type="checkbox" name="notification_enabled" value="1" class="checkbox" @checked(old('notification_enabled', $setting->notification_enabled))>
      <span class="label-text ml-2">LINE通知を有効にする</span>
    </label>
  </div>

  <!-- 反応で通知（チェックボックス） -->
  <div class="form-control hidden">
    <label class="label cursor-pointer">
      <input type="hidden" name="notify_on_reaction" value="0">
      <input type="checkbox" name="notify_on_reaction" value="1" class="checkbox" @checked(old('notify_on_reaction', $setting->notify_on_reaction))>
      <span class="label-text ml-2">いいねやリアクションで通知(未実装)</span>
    </label>
  </div>

  <!-- コメントで通知（チェックボックス） -->
  <div class="form-control hidden">
    <label class="label cursor-pointer">
      <input type="hidden" name="notify_on_comment" value="0">
      <input type="checkbox" name="notify_on_comment" value="1" class="checkbox" @checked(old('notify_on_comment', $setting->notify_on_comment))>
      <span class="label-text ml-2">コメントで通知(未実装)</span>
    </label>
  </div>

  <!-- 送信ボタン -->
  <div class="form-control">
    <input type="submit" value="通知設定を作成" class="btn btn-success">
  </div>
</form>
