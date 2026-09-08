"use client";

import { useEffect, useRef, useState } from "react";

const FRAMES = [
  {
    src: "/demo/frame-1.png",
    alt: "Tidimail recommending Unsubscribe for a shop.",
  },
  {
    src: "/demo/frame-2.png",
    alt: "Tidimail Digest with Undo.",
  },
  {
    src: "/demo/frame-3.png",
    alt: "Tidimail Keep, Digest, and Unsubscribe.",
  },
] as const;

const FRAME_MS = 3800;

export function DemoPlayer() {
  const videoRef = useRef<HTMLVideoElement>(null);
  const [file, setFile] = useState<string | null>(null);
  const [playing, setPlaying] = useState(false);
  const [frame, setFrame] = useState(0);
  const [progress, setProgress] = useState(0);

  useEffect(() => {
    let cancelled = false;
    fetch("/demo.mp4", { method: "HEAD" })
      .then((response) => {
        if (!cancelled && response.ok) setFile("/demo.mp4");
      })
      .catch(() => undefined);
    return () => {
      cancelled = true;
    };
  }, []);

  useEffect(() => {
    if (!playing || file) return;
    const started = Date.now();
    const tick = window.setInterval(() => {
      const elapsed = Date.now() - started;
      const total = FRAMES.length * FRAME_MS;
      const position = elapsed % total;
      setFrame(Math.floor(position / FRAME_MS));
      setProgress((position / total) * 100);
    }, 80);
    return () => window.clearInterval(tick);
  }, [playing, file]);

  function play() {
    setPlaying(true);
    setFrame(0);
    setProgress(0);
    if (file && videoRef.current) {
      void videoRef.current.play();
    }
  }

  function pause() {
    setPlaying(false);
    videoRef.current?.pause();
  }

  return (
    <div className="relative overflow-hidden rounded-[1.75rem] border border-ink-900/10 bg-ink-900 shadow-card">
      {file ? (
        <video
          ref={videoRef}
          className="aspect-[4/3] w-full object-cover"
          poster={FRAMES[0].src}
          src={file}
          playsInline
          muted
          loop
          onPlay={() => setPlaying(true)}
          onPause={() => setPlaying(false)}
          onTimeUpdate={(event) => {
            const node = event.currentTarget;
            if (node.duration) setProgress((node.currentTime / node.duration) * 100);
          }}
        />
      ) : (
        <img
          src={FRAMES[frame].src}
          alt={FRAMES[frame].alt}
          className="aspect-[4/3] w-full object-cover"
        />
      )}

      {!playing ? (
        <button
          type="button"
          onClick={play}
          className="absolute inset-0 grid place-items-center bg-ink-900/25"
          aria-label="Play demo"
        >
          <span className="grid h-16 w-16 place-items-center rounded-full bg-linen-50 text-ink-900 shadow-card">
            <PlayIcon />
          </span>
        </button>
      ) : (
        <button type="button" onClick={pause} className="absolute inset-0" aria-label="Pause demo" />
      )}

      <div className="pointer-events-none absolute inset-x-0 bottom-0 px-4 pb-4 pt-10">
        <div className="h-1 overflow-hidden rounded-full bg-linen-50/25">
          <div className="h-full rounded-full bg-linen-50" style={{ width: `${playing ? progress : 0}%` }} />
        </div>
      </div>
    </div>
  );
}

function PlayIcon() {
  return (
    <svg width="22" height="22" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true" className="ml-0.5">
      <path d="M8 5.14v13.72L19.12 12 8 5.14z" />
    </svg>
  );
}
