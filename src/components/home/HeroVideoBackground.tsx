"use client";

import { useEffect, useRef, useState } from "react";
import Image from "next/image";

interface HeroVideoBackgroundProps {
  posterSrc: string;
  posterAlt: string;
  webmSrc?: string;
  mp4Src?: string;
}

/**
 * Фоновое видео первого экрана: автозапуск без звука, зацикленное, корректно
 * работает на телефонах (playsInline). При ошибке загрузки, отсутствии файлов
 * или включённом «уменьшить движение» показывается статичное изображение.
 */
export function HeroVideoBackground({ posterSrc, posterAlt, webmSrc, mp4Src }: HeroVideoBackgroundProps) {
  const videoRef = useRef<HTMLVideoElement>(null);
  const [hasError, setHasError] = useState(false);
  const [isPlaying, setIsPlaying] = useState(false);
  const [prefersReducedMotion, setPrefersReducedMotion] = useState(
    () => typeof window !== "undefined" && window.matchMedia("(prefers-reduced-motion: reduce)").matches,
  );

  const hasSources = Boolean(webmSrc || mp4Src);
  const showVideo = hasSources && !hasError;

  useEffect(() => {
    const mediaQuery = window.matchMedia("(prefers-reduced-motion: reduce)");
    function handleChange(e: MediaQueryListEvent) {
      setPrefersReducedMotion(e.matches);
    }
    mediaQuery.addEventListener("change", handleChange);
    return () => mediaQuery.removeEventListener("change", handleChange);
  }, []);

  useEffect(() => {
    const video = videoRef.current;
    if (!video || !showVideo || prefersReducedMotion) return;

    video
      .play()
      .then(() => setIsPlaying(true))
      .catch(() => setIsPlaying(false));
  }, [showVideo, prefersReducedMotion]);

  function togglePlay() {
    const video = videoRef.current;
    if (!video) return;
    if (video.paused) {
      video
        .play()
        .then(() => setIsPlaying(true))
        .catch(() => {});
    } else {
      video.pause();
      setIsPlaying(false);
    }
  }

  return (
    <div className="absolute inset-0">
      <Image src={posterSrc} alt={posterAlt} fill priority sizes="100vw" className="object-cover" />

      {showVideo && !prefersReducedMotion ? (
        <video
          ref={videoRef}
          className="absolute inset-0 h-full w-full object-cover"
          muted
          loop
          playsInline
          autoPlay
          preload="metadata"
          poster={posterSrc}
          aria-hidden="true"
          onError={() => setHasError(true)}
          onPlaying={() => setIsPlaying(true)}
          onPause={() => setIsPlaying(false)}
        >
          {webmSrc ? <source src={webmSrc} type="video/webm" /> : null}
          {mp4Src ? <source src={mp4Src} type="video/mp4" /> : null}
        </video>
      ) : null}

      {showVideo && !prefersReducedMotion ? (
        <button
          type="button"
          onClick={togglePlay}
          aria-pressed={isPlaying}
          aria-label={isPlaying ? "Остановить видео" : "Воспроизвести видео"}
          className="absolute bottom-5 right-5 z-10 flex h-11 w-11 items-center justify-center rounded-full bg-black/45 text-white backdrop-blur-sm transition-colors hover:bg-black/65"
        >
          {isPlaying ? (
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
              <rect x="3" y="2" width="3.5" height="12" rx="1" fill="currentColor" />
              <rect x="9.5" y="2" width="3.5" height="12" rx="1" fill="currentColor" />
            </svg>
          ) : (
            <svg width="16" height="16" viewBox="0 0 16 16" fill="none" aria-hidden="true">
              <path d="M4 2.5v11l10-5.5-10-5.5z" fill="currentColor" />
            </svg>
          )}
        </button>
      ) : null}
    </div>
  );
}
