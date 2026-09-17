import { useState } from 'react'
import { Icon } from '../components/Icon'
import { AppStatusBadge, StatusSteps } from '../components/ui'
import { levelLabel, offers, skillProfile } from '../data/demo'
import { useStore } from '../store'
import { matchOffer } from './Career'

const stateText = { ok: 'Подтверждено', gap: 'Ниже требуемого', unknown: 'Не оценено' } as const

export function OfferDetail({ id }: { id: string }) {
  const { openOffer, applications, apply, withdraw, openReport } = useStore()
  const [share, setShare] = useState(true)
  const o = offers.find(x => x.id === id)!
  const idx = offers.indexOf(o)
  const m = matchOffer(o)
  const app = applications.find(a => a.offerId === o.id && a.status !== 'Отозвана')

  return (
    <div className="screen">
      <header className={`bg-${o.accent}`} style={{ padding: '14px 16px 24px' }}>
        <button className="icon-btn" style={{ background: 'rgba(11,13,16,.1)' }} onClick={() => openOffer(null)} aria-label="Назад">
          <Icon name="left" size={20} />
        </button>
        <div className="eyebrow" style={{ marginTop: 22 }}>0{idx + 1} · {o.company}</div>
        <h1 className="display" style={{ fontSize: 30, marginTop: 8 }}>{o.title}</h1>
        <div className="facts">
          <div><span>Формат</span>{o.format}</div>
          <div><span>Сроки</span>{o.period}</div>
          <div><span>Мест</span>{o.places}</div>
          <div><span>Приём</span>{o.deadline}</div>
        </div>
      </header>

      {app && (
        <div className="section">
          <div className="card" style={{ borderColor: 'var(--ink)' }}>
            <div className="row between">
              <span style={{ fontWeight: 800 }}>Ваша заявка</span>
              <AppStatusBadge status={app.status} />
            </div>
            <div style={{ marginTop: 14 }}><StatusSteps status={app.status} /></div>
          </div>
        </div>
      )}

      <div className="section">
        <div className="section-head"><h2 className="display h2">Задачи</h2></div>
        <div className="card stack" style={{ gap: 10 }}>
          {o.tasks.map((t, i) => (
            <div key={t} className="row" style={{ alignItems: 'flex-start', gap: 12 }}>
              <span className="eyebrow muted" style={{ marginTop: 3 }}>0{i + 1}</span>
              <span>{t}</span>
            </div>
          ))}
        </div>
      </div>

      <div className="section">
        <div className="section-head">
          <h2 className="display h2">Навыки</h2>
          <span className="tiny muted">тест {skillProfile.version}</span>
        </div>
        <div className="card" style={{ padding: 0 }}>
          {m.map((r, i) => (
            <div key={r.skill} className="req-row" style={{ borderTop: i ? '1px solid var(--line)' : 0 }}>
              <span className={`req-ic ${r.state}`}>
                <Icon name={r.state === 'ok' ? 'check' : r.state === 'gap' ? 'down' : 'help'} size={14} stroke={2.6} />
              </span>
              <div style={{ flex: 1 }}>
                <div style={{ fontWeight: 700 }}>{r.skill}</div>
                <div className="tiny muted">Нужно: {levelLabel[r.level].toLowerCase()} · у вас: {levelLabel[r.mine].toLowerCase()}</div>
              </div>
              <span className="tiny" style={{ fontWeight: 700 }}>{stateText[r.state]}</span>
            </div>
          ))}
        </div>
        <p className="tiny muted" style={{ marginTop: 8 }}>
          Непроверенные навыки работодатель уточнит на собеседовании. Автоматического отказа по тесту нет.
        </p>
      </div>

      <div className="section">
        {app ? (
          <div className="stack">
            <button className="btn btn-ghost btn-block"
              onClick={() => openReport({ title: 'Помощь с практикой', context: `Заявка · ${o.company}, ${o.title}`, owner: 'Центр карьеры' })}>
              <Icon name="chat" size={18} />Нужна помощь координатора
            </button>
            {(app.status === 'Подана' || app.status === 'На рассмотрении') && (
              <button className="btn btn-block" style={{ color: 'var(--danger)' }} onClick={() => withdraw(app.id)}>Отозвать заявку</button>
            )}
          </div>
        ) : (
          <>
            <label className="card row share" style={{ gap: 12, cursor: 'pointer' }}>
              <div style={{ flex: 1 }}>
                <div style={{ fontWeight: 800 }}>Приложить результаты диагностики</div>
                <div className="tiny muted" style={{ marginTop: 2 }}>Увидит только {o.company}. Доступ можно отозвать.</div>
              </div>
              <input type="checkbox" className="switch" checked={share} onChange={e => setShare(e.target.checked)} />
            </label>
            <button className="btn btn-lime btn-block" style={{ marginTop: 12, minHeight: 52 }} onClick={() => apply(o.id, share)}>
              Подать заявку <Icon name="arrow" size={18} />
            </button>
          </>
        )}
        <div className="tiny muted" style={{ marginTop: 14 }}>Контакт: {o.contact}. Обновлено {o.updated}</div>
      </div>
    </div>
  )
}
