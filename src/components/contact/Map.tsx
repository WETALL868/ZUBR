import { siteConfig } from "@/config/site";

export function Map() {
  const src = siteConfig.map.provider === "2gis" ? siteConfig.map.twoGisEmbedUrl : siteConfig.map.yandexEmbedUrl;

  return (
    <div className="aspect-[4/3] w-full overflow-hidden rounded-2xl border border-border sm:aspect-video">
      <iframe
        src={src}
        title="Карта проезда к офису"
        loading="lazy"
        referrerPolicy="no-referrer-when-downgrade"
        className="h-full w-full"
      />
    </div>
  );
}
