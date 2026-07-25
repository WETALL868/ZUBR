import Script from "next/script";
import { siteConfig } from "@/config/site";

/**
 * Подключает Яндекс.Метрику и Google Analytics, если идентификаторы заданы
 * в переменных окружения NEXT_PUBLIC_YANDEX_METRIKA_ID / NEXT_PUBLIC_GA_ID.
 * Если идентификаторы не указаны, компонент ничего не рендерит и не вызывает ошибок.
 */
export function Analytics() {
  const { yandexMetrikaId, googleAnalyticsId } = siteConfig.analytics;

  return (
    <>
      {yandexMetrikaId ? (
        <>
          <Script id="yandex-metrika" strategy="afterInteractive">
            {`
              (function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
              m[i].l=1*new Date();
              for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }}
              k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)})
              (window, document, "script", "https://mc.yandex.ru/metrika/tag.js", "ym");
              ym(${yandexMetrikaId}, "init", { clickmap:true, trackLinks:true, accurateTrackBounce:true, webvisor:false });
            `}
          </Script>
          <noscript>
            <div>
              {/* eslint-disable-next-line @next/next/no-img-element -- официальный пиксель Яндекс.Метрики для noscript, next/image здесь неприменим */}
              <img
                src={`https://mc.yandex.ru/watch/${yandexMetrikaId}`}
                style={{ position: "absolute", left: "-9999px" }}
                alt=""
              />
            </div>
          </noscript>
        </>
      ) : null}

      {googleAnalyticsId ? (
        <>
          <Script src={`https://www.googletagmanager.com/gtag/js?id=${googleAnalyticsId}`} strategy="afterInteractive" />
          <Script id="google-analytics" strategy="afterInteractive">
            {`
              window.dataLayer = window.dataLayer || [];
              function gtag(){dataLayer.push(arguments);}
              gtag('js', new Date());
              gtag('config', '${googleAnalyticsId}');
            `}
          </Script>
        </>
      ) : null}
    </>
  );
}
