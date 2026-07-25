import type { Metadata } from "next";
import { Manrope } from "next/font/google";
import { siteConfig } from "@/config/site";
import { Header } from "@/components/layout/Header";
import { Footer } from "@/components/layout/Footer";
import { MobileBottomBar } from "@/components/layout/MobileBottomBar";
import { FloatingContact } from "@/components/layout/FloatingContact";
import { BackToTop } from "@/components/layout/BackToTop";
import { CookieConsent } from "@/components/layout/CookieConsent";
import { Analytics } from "@/components/layout/Analytics";
import { LeadModalProvider } from "@/components/lead/LeadModalProvider";
import { JsonLd } from "@/components/seo/JsonLd";
import { buildLocalBusinessSchema } from "@/lib/structured-data";
import "./globals.css";

const manrope = Manrope({
  variable: "--font-manrope",
  subsets: ["latin", "cyrillic"],
  display: "swap",
});

export const metadata: Metadata = {
  metadataBase: new URL(siteConfig.seo.siteUrl),
  title: {
    default: siteConfig.seo.defaultTitle,
    template: `%s — ${siteConfig.companyName}`,
  },
  description: siteConfig.seo.defaultDescription,
  openGraph: {
    type: "website",
    locale: "ru_RU",
    siteName: siteConfig.companyName,
    title: siteConfig.seo.defaultTitle,
    description: siteConfig.seo.defaultDescription,
  },
  twitter: {
    card: "summary_large_image",
    title: siteConfig.seo.defaultTitle,
    description: siteConfig.seo.defaultDescription,
  },
  alternates: {
    canonical: "/",
  },
};

export default function RootLayout({ children }: Readonly<{ children: React.ReactNode }>) {
  return (
    <html lang="ru" data-scroll-behavior="smooth" className={`${manrope.variable} h-full antialiased`}>
      <body className="flex min-h-full flex-col bg-bg text-text">
        <JsonLd data={buildLocalBusinessSchema()} />
        <Analytics />
        <LeadModalProvider>
          <Header />
          <main className="flex-1 pb-20 xl:pb-0">{children}</main>
          <Footer />
          <MobileBottomBar />
          <FloatingContact />
          <BackToTop />
          <CookieConsent />
        </LeadModalProvider>
      </body>
    </html>
  );
}
