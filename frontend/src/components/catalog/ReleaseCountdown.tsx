"use client";

import { useEffect, useState } from "react";

const ZERO = { days: 0, hours: 0, minutes: 0, seconds: 0 };

/**
 * Calcule le temps restant avant une date cible.
 *
 * @param target Date de sortie
 * @returns Composantes du compte à rebours
 */
function diff(target: Date) {
  const total = Math.max(0, target.getTime() - Date.now());
  const days = Math.floor(total / 86400000);
  const hours = Math.floor((total % 86400000) / 3600000);
  const minutes = Math.floor((total % 3600000) / 60000);
  const seconds = Math.floor((total % 60000) / 1000);
  return { days, hours, minutes, seconds };
}

/**
 * Compte à rebours jusqu'à la sortie officielle d'un livre.
 */
export function ReleaseCountdown({
  date,
  className,
}: {
  date: string;
  className?: string;
}) {
  const [time, setTime] = useState(ZERO);

  useEffect(() => {
    const target = new Date(date);

    const update = () => {
      setTime(diff(target));
    };

    update();
    const id = setInterval(update, 1000);
    return () => clearInterval(id);
  }, [date]);

  const units = [
    { value: time.days, label: "Jours" },
    { value: time.hours, label: "Heures" },
    { value: time.minutes, label: "Minutes" },
    { value: time.seconds, label: "Secondes" },
  ];

  return (
    <div className={className}>
      <div className="grid grid-cols-4 gap-3 sm:gap-4">
        {units.map((unit) => (
          <div
            key={unit.label}
            className="flex flex-col items-center rounded-2xl border border-white/10 bg-white/[0.03] px-2 py-4 backdrop-blur-sm"
          >
            <span className="font-display text-3xl tabular-nums text-white sm:text-4xl">
              {String(unit.value).padStart(2, "0")}
            </span>
            <span className="mt-1 text-[0.6rem] uppercase tracking-[0.22em] text-white/45">
              {unit.label}
            </span>
          </div>
        ))}
      </div>
    </div>
  );
}
