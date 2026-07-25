import { ImageResponse } from "next/og";

export const size = { width: 180, height: 180 };
export const contentType = "image/png";

export default function AppleIcon() {
  return new ImageResponse(
    (
      <div
        style={{
          width: "100%",
          height: "100%",
          display: "flex",
          alignItems: "center",
          justifyContent: "center",
          background: "#174C48",
        }}
      >
        <div
          style={{
            color: "#F5F3EE",
            fontSize: 108,
            fontWeight: 800,
            fontFamily: "Arial, sans-serif",
          }}
        >
          П
        </div>
      </div>
    ),
    { ...size },
  );
}
