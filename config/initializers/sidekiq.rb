require "sidekiq"
require "sidekiq-cron"

Sidekiq.configure_server do |config|
  config.redis = {
    url: ENV.fetch("REDIS_URL", "redis://redis:6379/0"),
    network_timeout: 5
  }
end

Sidekiq.configure_client do |config|
  config.redis = {
    url: ENV.fetch("REDIS_URL", "redis://redis:6379/0"),
    network_timeout: 5
  }
end


if File.exist?(Rails.root.join("config", "sidekiq.yml"))
  sidekiq_config = YAML.load_file(Rails.root.join("config", "sidekiq.yml"))
  if sidekiq_config[:cron]
    begin
      Sidekiq::Cron::Job.load_from_hash sidekiq_config[:cron]
    rescue RedisClient::CannotConnectError, Socket::ResolutionError => e
      # CI環境など、Redisが利用できない場合はcronジョブの設定をスキップ
      Rails.logger.warn("Redis connection failed during Sidekiq cron setup: #{e.message}")
    end
  end
end
