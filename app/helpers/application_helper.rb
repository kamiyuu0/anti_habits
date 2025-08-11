module ApplicationHelper
  def default_meta_tags
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
        image: image_url("Anti_Habits.png"),
        locale: "ja-JP"
      },
        twitter: {
        card: "summary_large_image",
        image: image_url("Anti_Habits.png")
      }
    }
  end
end
