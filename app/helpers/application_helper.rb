module ApplicationHelper
  def default_meta_tags(url: "https://res.cloudinary.com/antihabits/image/upload/v1757773129/anti_habits_static_ogp_zg7q8j.png")
    {
      site: "Anti Habits",
      title: "Anti Habits",
      reverse: true,
      charset: "utf-8",
      description: "「Anti Habits」は、悪習慣排除を手助けするアプリです。",
      canonical: request.original_url,
      og: {
        site_name: :site,
        title: :title,
        description: :description,
        type: "website",
        url: request.original_url,
        image: url,
        locale: "ja-JP"
      },
        twitter: {
        card: "summary_large_image",
        image: url
      }
    }
  end
end
