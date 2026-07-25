import Link from "next/link";
import { siteConfig } from "@/config/site";

export function Logo({ className }: { className?: string }) {
  return (
    <Link href="/" className={`flex flex-col leading-none ${className ?? ""}`} aria-label={siteConfig.companyName}>
      <span className="text-xl font-extrabold tracking-tight text-text sm:text-2xl">
        {siteConfig.companyShortName}
      </span>
      <span className="mt-0.5 text-[11px] font-medium uppercase tracking-wider text-accent">
        Ворота под ключ
      </span>
    </Link>
  );
}
