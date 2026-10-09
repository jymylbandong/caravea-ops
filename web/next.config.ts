import type { NextConfig } from "next";

const nextConfig: NextConfig = {
  // The app has a single section, so send "/" straight to the bookings list.
  async redirects() {
    return [{ source: "/", destination: "/bookings", permanent: false }];
  },
  cacheComponents: true,
  partialPrefetching: true,
  turbopack: {
    rules: {
      "*.css": {
        loaders: ["@tailwindcss/turbopack"],
        as: "*.css",
      },
    },
  },
};

export default nextConfig;
