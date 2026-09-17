import { useState, type ReactNode } from 'react'
import { Icon, type IconName } from './Icon'
import { useStore, type Tab } from '../store'
import type { AppStatus, TicketStatus } from '../data/demo'

export function Logo() {
  return (
    <div className="logo">
      <div className="logo-mark">С</div>
      <div className="logo-text">Цифровой<br />помощник СГТУ</div>
    </div>
  )
}

export function DemoBadge() {
  return <span className="badge badge-demo">Демо-данные</span>
}

const tabs: { id: Tab; label: string; icon: IconName }[] = [
  { id: 'home', label: 'Главная', icon: 'home' },
  { id: 'schedule', label: 'Расписание', icon: 'calendar' },
  { id: 'career', label: 'Карьера', icon: 'briefcase' },
  { id: 'help', label: 'Помощь', icon: 'help' },
]

export function TabBar() {
  const { tab, go } = useStore()
  return (
    <nav className="tabbar" aria-label="Разделы">
      {tabs.map(t => (
        <button key={t.id} className={`tab ${tab === t.id ? 'active' : ''}`} onClick={() => go(t.id)}
          aria-current={tab === t.id ? 'page' : undefined}>
          <span className="tab-ic"><Icon name={t.icon} size={19} /></span>
          {t.label}
        </button>
      ))}
    </nav>
  )
}

export function PageHeader({ title, eyebrow, right }: { title: ReactNode; eyebrow?: ReactNode; right?: ReactNode }) {
  return (
    <header className="dark" style={{ paddingBottom: 22 }}>
      <div className="topbar">
        <Logo />
        <DemoBadge />
      </div>
      <div style={{ padding: '10px 16px 0' }}>
        {eyebrow && <div style={{ marginBottom: 12 }}>{eyebrow}</div>}
        <div className="row between" style={{ alignItems: 'flex-end' }}>
          <h1 className="display h1">{title}</h1>
          {right}
        </div>
      </div>
    </header>
  )
}

const appTone: Record<AppStatus, string> = {
  'Подана': 'badge-soft',
  'На рассмотрении': 'badge-blue',
  'Принят': 'badge-lime',
  'Отказ': 'badge-coral',
  'Отозвана': 'badge-soft',
}

export function AppStatusBadge({ status }: { status: AppStatus }) {
  return <span className={`badge ${appTone[status]}`}><span className="dot" />{status}</span>
}

const ticketTone: Record<TicketStatus, string> = {
  'Новое': 'badge-coral',
  'В работе': 'badge-blue',
  'Решено': 'badge-lime',
}

export function TicketBadge({ status }: { status: TicketStatus }) {
  return <span className={`badge ${ticketTone[status]}`}><span className="dot" />{status}</span>
}

/** Шкала статуса заявки: Подана → На рассмотрении → Решение */
export function StatusSteps({ status, dark }: { status: AppStatus; dark?: boolean }) {
  const steps = ['Подана', 'Рассмотрение', status === 'Отказ' ? 'Отказ' : 'Принят']
  const reached = status === 'Подана' ? 0 : status === 'На рассмотрении' ? 1 : status === 'Отозвана' ? 0 : 2
  const base = dark ? 'var(--ink-line)' : 'var(--line)'
  return (
    <div>
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: 4 }}>
        {steps.map((_, i) => (
          <div key={i} style={{ height: 6, borderRadius: 6, background: i <= reached ? (dark ? 'var(--lime)' : 'var(--ink)') : base }} />
        ))}
      </div>
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(3, 1fr)', gap: 4, marginTop: 6 }}>
        {steps.map((s, i) => (
          <span key={s} className="tiny" style={{ fontWeight: i === reached ? 700 : 500, opacity: i <= reached ? 1 : .55, textAlign: i === 2 ? 'right' : i === 1 ? 'center' : 'left' }}>{s}</span>
        ))}
      </div>
    </div>
  )
}

export function ReportSheet() {
  const { report, openReport, submitReport } = useStore()
  const [text, setText] = useState('')
  if (!report) return null
  return (
    <div className="sheet-backdrop" onClick={() => openReport(null)}>
      <div className="sheet" role="dialog" aria-modal="true" aria-label={report.title} onClick={e => e.stopPropagation()}>
        <div className="sheet-grip" />
        <div className="row between" style={{ alignItems: 'flex-start' }}>
          <h2 className="display h2">{report.title}</h2>
          <button className="icon-btn on-light" onClick={() => openReport(null)} aria-label="Закрыть"><Icon name="close" size={18} /></button>
        </div>
        <div className="card" style={{ background: 'var(--paper)', marginTop: 14, padding: 12 }}>
          <div className="eyebrow muted">Контекст сохранится</div>
          <div style={{ fontWeight: 700, marginTop: 4 }}>{report.context}</div>
        </div>
        <textarea className="field" style={{ marginTop: 12 }} placeholder="Опишите, что не так"
          value={text} onChange={e => setText(e.target.value)} />
        <div className="row small muted" style={{ margin: '10px 0 16px', alignItems: 'flex-start' }}>
          <span style={{ marginTop: 2 }}><Icon name="lock" size={15} /></span>
          <span>Конфиденциально, не анонимно. Видят: {report.owner} и координатор пилота.</span>
        </div>
        <button className="btn btn-ink btn-block" onClick={() => { submitReport(text); setText('') }}>Отправить обращение</button>
      </div>
    </div>
  )
}

export function Toast() {
  const { toast } = useStore()
  if (!toast) return null
  return <div className="toast" role="status"><Icon name="check" size={18} stroke={2.6} />{toast}</div>
}
