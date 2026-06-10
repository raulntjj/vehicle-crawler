import type { NextConfig } from "next";

const backendUrl = process.env.BACKEND_URL ?? "http://localhost:8080";

const nextConfig: NextConfig = {
  // Enables minimal standalone output for Docker — no full node_modules needed at runtime
  output: "standalone",

  async rewrites() {
    return [
      {
        source: "/api/v1/:path*",
        destination: `${backendUrl}/api/:path*`,
      },
    ];
  },
};

export default nextConfig;
