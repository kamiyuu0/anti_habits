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
    Sidekiq::Cron::Job.load_from_hash sidekiq_config[:cron]
  end
end
