/** @type {import('tailwindcss').Config} */
module.exports = {
  content: ["./src/**/*.{js,ts,jsx,tsx}"],
  theme: {
    extend: {
      colors: {
        linen: {
          50: "#fbf8f2",
          100: "#f4efe6",
          200: "#e8dfd0",
          300: "#d4c6b0",
        },
        ink: {
          700: "#3d342c",
          800: "#2a231e",
          900: "#1c1713",
        },
        sage: {
          50: "#eef5f1",
          600: "#2f5d50",
          700: "#24483e",
        },
        clay: {
          500: "#c45c38",
          600: "#a44a2c",
        },
        gold: {
          500: "#b08948",
        },
      },
      fontFamily: {
        serif: ["var(--font-serif)", "Georgia", "serif"],
        sans: ["var(--font-sans)", "system-ui", "sans-serif"],
      },
      boxShadow: {
        card: "0 1px 0 rgba(28, 23, 19, 0.04), 0 18px 40px -24px rgba(28, 23, 19, 0.28)",
      },
    },
  },
  plugins: [],
};
