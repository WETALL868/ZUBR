"use client";

import { useEffect, useRef } from "react";
import Image from "next/image";

interface LightboxProps {
  images: { src: string; alt: string }[];
  index: number;
  onClose: () => void;
  onNavigate: (index: number) => void;
}

export function Lightbox({ images, index, onClose, onNavigate }: LightboxProps) {
  const dialogRef = useRef<HTMLDialogElement>(null);

  useEffect(() => {
    const dialog = dialogRef.current;
    if (!dialog) return;
    if (!dialog.open) {
      dialog.showModal();
      document.body.style.overflow = "hidden";
    }
    return () => {
      document.body.style.overflow = "";
    };
  }, []);

  useEffect(() => {
    function onKeyDown(e: KeyboardEvent) {
      if (e.key === "ArrowRight") onNavigate((index + 1) % images.length);
      if (e.key === "ArrowLeft") onNavigate((index - 1 + images.length) % images.length);
    }
    window.addEventListener("keydown", onKeyDown);
    return () => window.removeEventListener("keydown", onKeyDown);
  }, [index, images.length, onNavigate]);

  const current = images[index];

  return (
    <dialog
      ref={dialogRef}
      onClose={onClose}
      onCancel={onClose}
      onClick={(e) => {
        if (e.target === dialogRef.current) onClose();
      }}
      aria-label="Просмотр фотографии"
      className="m-0 h-full max-h-none w-full max-w-none border-0 bg-black/90 p-0 backdrop:bg-black/90"
    >
      <div className="relative flex h-full w-full items-center justify-center p-4 sm:p-10">
        <button
          type="button"
          onClick={onClose}
          aria-label="Закрыть просмотр"
          className="absolute right-4 top-4 flex h-11 w-11 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20"
        >
          <svg width="18" height="18" viewBox="0 0 16 16" fill="none" aria-hidden="true">
            <path d="M1 1l14 14M15 1L1 15" stroke="currentColor" strokeWidth="1.7" strokeLinecap="round" />
          </svg>
        </button>

        {images.length > 1 ? (
          <button
            type="button"
            onClick={() => onNavigate((index - 1 + images.length) % images.length)}
            aria-label="Предыдущее фото"
            className="absolute left-2 top-1/2 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20 sm:left-6"
          >
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M15 5l-7 7 7 7" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
            </svg>
          </button>
        ) : null}

        <div className="relative h-full max-h-[80vh] w-full max-w-4xl">
          <Image src={current.src} alt={current.alt} fill sizes="100vw" className="object-contain" />
        </div>

        {images.length > 1 ? (
          <button
            type="button"
            onClick={() => onNavigate((index + 1) % images.length)}
            aria-label="Следующее фото"
            className="absolute right-2 top-1/2 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20 sm:right-6"
          >
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" aria-hidden="true">
              <path d="M9 5l7 7-7 7" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" />
            </svg>
          </button>
        ) : null}

        <p className="absolute bottom-4 left-1/2 -translate-x-1/2 text-sm text-white/70">
          {current.alt} · {index + 1} / {images.length}
        </p>
      </div>
    </dialog>
  );
}
