"use client";

import { createContext, useCallback, useContext, useMemo, useState, type ReactNode } from "react";
import { Modal } from "@/components/ui/Modal";
import { Quiz } from "./Quiz";
import { LeadForm } from "./LeadForm";
import { trackGoal } from "@/lib/analytics";

type ModalMode = "calculator" | "measurement" | null;

interface LeadModalContextValue {
  openCalculator: (gateType?: string) => void;
  openMeasurement: () => void;
  close: () => void;
}

const LeadModalContext = createContext<LeadModalContextValue | null>(null);

export function LeadModalProvider({ children }: { children: ReactNode }) {
  const [mode, setMode] = useState<ModalMode>(null);
  const [gateType, setGateType] = useState<string>("");

  const openCalculator = useCallback((gate?: string) => {
    setGateType(gate ?? "");
    setMode("calculator");
    trackGoal("calculator_open");
  }, []);

  const openMeasurement = useCallback(() => {
    setMode("measurement");
  }, []);

  const close = useCallback(() => setMode(null), []);

  const value = useMemo(() => ({ openCalculator, openMeasurement, close }), [openCalculator, openMeasurement, close]);

  return (
    <LeadModalContext.Provider value={value}>
      {children}
      <Modal isOpen={mode === "calculator"} onClose={close} title="Расчёт стоимости ворот">
        <Quiz onDone={close} initialGateType={gateType} />
      </Modal>
      <Modal isOpen={mode === "measurement"} onClose={close} title="Вызвать замерщика">
        <p className="mb-4 text-sm text-text-muted">
          Оставьте имя и телефон — уточним удобное время выезда специалиста на замер.
        </p>
        <LeadForm source="measurement" submitLabel="Вызвать замерщика" showComment commentLabel="Комментарий" />
      </Modal>
    </LeadModalContext.Provider>
  );
}

export function useLeadModal() {
  const ctx = useContext(LeadModalContext);
  if (!ctx) throw new Error("useLeadModal должен использоваться внутри LeadModalProvider");
  return ctx;
}
