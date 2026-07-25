import { ImageResponse } from "next/og";
import { siteConfig } from "@/config/site";

export const size = { width: 1200, height: 630 };
export const contentType = "image/png";

export default function OpengraphImage() {
  return new ImageResponse(
    (
      <div
        style={{
          width: "100%",
          height: "100%",
          display: "flex",
          flexDirection: "column",
          justifyContent: "center",
          padding: "80px",
          background: "#F5F3EE",
        }}
      >
        <div style={{ display: "flex", alignItems: "center", gap: 16 }}>
          <div style={{ width: 14, height: 56, background: "#B98242", borderRadius: 7 }} />
          <div style={{ fontSize: 40, fontWeight: 800, color: "#174C48", fontFamily: "Arial, sans-serif" }}>
            {siteConfig.companyName}
          </div>
        </div>
        <div
          style={{
            marginTop: 40,
            fontSize: 56,
            fontWeight: 800,
            color: "#171A1D",
            fontFamily: "Arial, sans-serif",
            lineHeight: 1.15,
            maxWidth: 980,
          }}
        >
          {siteConfig.tagline}
        </div>
        <div style={{ marginTop: 28, fontSize: 28, color: "#62676C", fontFamily: "Arial, sans-serif" }}>
          {`Замер · Изготовление · Монтаж · Автоматика · ${siteConfig.cityAndRegion}`}
        </div>
      </div>
    ),
    { ...size },
  );
}
