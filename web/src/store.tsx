import { createContext, useCallback, useContext, useMemo, useRef, useState, type ReactNode } from 'react'
import {
  initialApplications, initialTickets,
  type Application, type Ticket,
} from './data/demo'

export type Tab = 'home' | 'schedule' | 'career' | 'help'
export type CareerView = 'offers' | 'skills' | 'applications'

export type ReportContext = { title: string; context: string; owner: string }

type Store = {
  tab: Tab
  go: (tab: Tab, careerView?: CareerView) => void
  careerView: CareerView
  setCareerView: (v: CareerView) => void
  offerId: string | null
  openOffer: (id: string | null) => void
  applications: Application[]
  apply: (offerId: string, withResults: boolean) => void
  withdraw: (applicationId: string) => void
  tickets: Ticket[]
  report: ReportContext | null
  openReport: (ctx: ReportContext | null) => void
  submitReport: (text: string) => void
  toast: string | null
  notify: (msg: string) => void
}

const StoreContext = createContext<Store | null>(null)

const today = '17 сент.'

export function StoreProvider({ children }: { children: ReactNode }) {
  const [tab, setTab] = useState<Tab>('home')
  const [careerView, setCareerView] = useState<CareerView>('offers')
  const [offerId, setOfferId] = useState<string | null>(null)
  const [applications, setApplications] = useState(initialApplications)
  const [tickets, setTickets] = useState(initialTickets)
  const [report, setReport] = useState<ReportContext | null>(null)
  const [toast, setToast] = useState<string | null>(null)
  const toastTimer = useRef<number | undefined>(undefined)

  const notify = useCallback((msg: string) => {
    setToast(msg)
    window.clearTimeout(toastTimer.current)
    toastTimer.current = window.setTimeout(() => setToast(null), 2600)
  }, [])

  const go = useCallback((next: Tab, view?: CareerView) => {
    setOfferId(null)
    setTab(next)
    if (view) setCareerView(view)
    window.scrollTo({ top: 0 })
  }, [])

  const openOffer = useCallback((id: string | null) => {
    setOfferId(id)
    window.scrollTo({ top: 0 })
  }, [])

  const apply = useCallback((id: string, withResults: boolean) => {
    setApplications(prev => {
      // Повторное нажатие не создаёт дубликат активной заявки.
      if (prev.some(a => a.offerId === id && a.status !== 'Отозвана')) return prev
      return [{ id: `a${Date.now()}`, offerId: id, status: 'Подана', withResults, history: [{ status: 'Подана', date: today }] }, ...prev]
    })
    notify('Заявка подана — статус в разделе «Заявки»')
  }, [notify])

  const withdraw = useCallback((applicationId: string) => {
    setApplications(prev => prev.map(a => a.id === applicationId
      ? { ...a, status: 'Отозвана', history: [...a.history, { status: 'Отозвана', date: today }] }
      : a))
    notify('Заявка отозвана')
  }, [notify])

  const submitReport = useCallback((text: string) => {
    if (!report) return
    setTickets(prev => [{
      id: `r${Date.now()}`,
      title: text.trim() || report.title,
      context: report.context,
      status: 'Новое',
      owner: report.owner,
      due: 'ответ до 20 сент.',
    }, ...prev])
    setReport(null)
    notify('Обращение отправлено. Статус — в «Помощи»')
  }, [report, notify])

  const value = useMemo<Store>(() => ({
    tab, go, careerView, setCareerView, offerId, openOffer,
    applications, apply, withdraw, tickets,
    report, openReport: setReport, submitReport, toast, notify,
  }), [tab, go, careerView, offerId, openOffer, applications, apply, withdraw, tickets, report, submitReport, toast, notify])

  return <StoreContext.Provider value={value}>{children}</StoreContext.Provider>
}

export function useStore() {
  const ctx = useContext(StoreContext)
  if (!ctx) throw new Error('useStore вне StoreProvider')
  return ctx
}
