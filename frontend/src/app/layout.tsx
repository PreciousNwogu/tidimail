import type { Metadata, Viewport } from "next";
import { Fraunces, Outfit } from "next/font/google";
import "./globals.css";
import { Providers } from "@/components/Providers";

const serif = Fraunces({
  subsets: ["latin"],
  variable: "--font-serif",
  display: "swap",
});

const sans = Outfit({
  subsets: ["latin"],
  variable: "--font-sans",
  display: "swap",
});

export const metadata: Metadata = {
  title: "Tidimail — tidy Gmail, you stay in control",
  description:
    "Tidimail starts in Review Mode. We look at senders, subjects, labels, and unsubscribe details — and change nothing until you choose Keep, Digest, or Unsubscribe.",
  applicationName: "Tidimail",
  appleWebApp: {
    capable: true,
    title: "Tidimail",
    statusBarStyle: "default",
  },
  formatDetection: {
    telephone: false,
  },
  icons: {
    icon: "/icon-192.png",
    apple: "/icon-192.png",
  },
};

export const viewport: Viewport = {
  themeColor: "#f4efe6",
  width: "device-width",
  initialScale: 1,
  viewportFit: "cover",
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="en" className={`${serif.variable} ${sans.variable}`}>
      <body className="font-sans antialiased">
        <div className="grain" />
        <Providers>{children}</Providers>
      </body>
    </html>
  );
}
