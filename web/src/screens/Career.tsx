import { useState } from 'react'
import { Icon } from '../components/Icon'
import { AppStatusBadge, PageHeader, StatusSteps } from '../components/ui'
import { levelLabel, offers, skillProfile, type Level, type Offer } from '../data/demo'
import { useStore, type CareerView } from '../store'

const order: Level[] = ['none', 'basic', 'applied', 'confident']

export function matchOffer(offer: Offer) {
  return offer.requires.map(r => {
    const mine = skillProfile.skills.find(s => s.title === r.skill)
    const level: Level = mine?.level ?? 'none'
    const state = level === 'none' ? 'unknown' : order.indexOf(level) >= order.indexOf(r.level) ? 'ok' : 'gap'
    return { ...r, mine: level, state } as const
  })
}

const views: { id: CareerView; label: string }[] = [
  { id: 'offers', label: 'Практики' },
  { id: 'skills', label: 'Мои навыки' },
  { id: 'applications', label: 'Заявки' },
]

export function Career() {
  const { careerView, setCareerView } = useStore()
  return (
    <div className="screen">
      <PageHeader eyebrow={<span className="badge badge-outline-lime">Практики и стажировки</span>} title="Карьера" />
      <div className="dark" style={{ padding: '0 16px 16px' }}>
        <div className="segmented" role="tablist">
          {views.map(v => (
            <button key={v.id} role="tab" aria-selected={careerView === v.id}
              className={careerView === v.id ? 'active' : ''} onClick={() => setCareerView(v.id)}>{v.label}</button>
          ))}
        </div>
      </div>
      {careerView === 'offers' && <Offers />}
      {careerView === 'skills' && <Skills />}
      {careerView === 'applications' && <Applications />}
    </div>
  )
}

const filters = [
  { id: 'all', label: 'Все' },
  { id: 'dev', label: 'Разработка' },
  { id: 'data', label: 'Данные' },
  { id: 'design', label: 'Дизайн' },
] as const

function Offers() {
  const { openOffer, applications } = useStore()
  const [filter, setFilter] = useState<(typeof filters)[number]['id']>('all')
  const list = offers.filter(o => filter === 'all' || o.direction === filter)

  return (
    <div className="section">
      <div className="chips" style={{ marginBottom: 14 }}>
        {filters.map(f => (
          <button key={f.id} className={`chip ${filter === f.id ? 'active' : ''}`} onClick={() => setFilter(f.id)}>{f.label}</button>
        ))}
      </div>
      <div className="stack" style={{ gap: 12 }}>
        {list.map((o, i) => {
          const m = matchOffer(o)
          const ok = m.filter(x => x.state === 'ok').length
          const applied = applications.find(a => a.offerId === o.id && a.status !== 'Отозвана')
          return (
            <button key={o.id} className={`card-accent bg-${o.accent} pressable offer`} onClick={() => openOffer(o.id)}>
              <div className="row between">
                <span className="eyebrow">0{i + 1} · {o.company}</span>
                {applied ? <span className="badge badge-ink">Заявка подана</span> : <Icon name="arrow" size={20} />}
              </div>
              <div className="display" style={{ fontSize: 21, marginTop: 14 }}>{o.title}</div>
              <div className="small" style={{ marginTop: 6, opacity: .8 }}>{o.format} · {o.period} · мест: {o.places}</div>
              <div className="row between" style={{ marginTop: 18 }}>
                <span className="tiny" style={{ fontWeight: 700 }}>{o.requires.map(r => r.skill).join(' · ')}</span>
              </div>
              <div className="offer-foot">
                <span className="row tiny" style={{ gap: 6, fontWeight: 700 }}>
                  <span className="match-dots">{m.map((x, k) => <i key={k} className={x.state} />)}</span>
                  {ok} из {m.length} навыков подтверждено
                </span>
                <span className="tiny" style={{ fontWeight: 700 }}>{o.deadline}</span>
              </div>
            </button>
          )
        })}
      </div>
    </div>
  )
}

function Skills() {
  const { notify } = useStore()
  const p = skillProfile
  return (
    <div className="section">
      <div className="card-dark" style={{ padding: 18, position: 'relative', overflow: 'hidden' }}>
        <div className="grade-orb" aria-hidden="true" />
        <div style={{ position: 'relative' }}>
          <span className="eyebrow" style={{ color: 'var(--lime)' }}>{p.track}</span>
          <div className="small" style={{ color: 'var(--muted-dark)', marginTop: 14 }}>Предварительный грейд</div>
          <div className="display" style={{ fontSize: 34, marginTop: 4 }}>{p.grade}</div>
          <div className="row wrap" style={{ marginTop: 12 }}>
            <span className="badge" style={{ background: 'var(--ink-3)', color: 'var(--muted-dark)' }}>Тест {p.version} · {p.date}</span>
            <span className="badge" style={{ background: 'rgba(239,130,97,.18)', color: 'var(--coral)' }}><Icon name="clock" size={12} />Практика {p.practice.toLowerCase()}</span>
          </div>
        </div>

        <div className="stack" style={{ gap: 14, marginTop: 22, position: 'relative' }}>
          {p.skills.map(s => (
            <div key={s.id}>
              <div className="row between small">
                <span style={{ fontWeight: 700 }}>{s.title}</span>
                <span style={{ color: s.level === 'none' ? 'var(--muted-dark)' : 'var(--lime)', fontWeight: 700 }}>
                  {levelLabel[s.level]}{s.score !== null && <span style={{ color: 'var(--muted-dark)', fontWeight: 500 }}> · {s.score}%</span>}
                </span>
              </div>
              <div className={`meter ${s.score === null ? 'hatched' : ''}`} style={{ marginTop: 8 }}><i style={{ width: `${s.score ?? 0}%` }} /></div>
            </div>
          ))}
        </div>

        <p className="tiny" style={{ color: 'var(--muted-dark)', marginTop: 18, position: 'relative' }}>
          «Не оценено» — нет данных, а не низкий результат. Грейд junior возможен только после проверки практического задания специалистом.
        </p>
      </div>

      <div className="section-head" style={{ marginTop: 24 }}><h2 className="display h2">Что подтянуть</h2></div>
      <div className="stack">
        {[
          { t: 'JavaScript: асинхронность', d: 'Промисы и async/await — 3 ошибки из 5 в диагностике', c: 'bg-lime' },
          { t: 'HTTP и API', d: 'Коды ответов и заголовки запросов', c: 'bg-blue' },
          { t: 'Git', d: 'Навык не проверялся — пройдите модуль', c: 'bg-coral' },
        ].map(r => (
          <div key={r.t} className="card row" style={{ gap: 12 }}>
            <span className={`rec-bar ${r.c}`} />
            <div style={{ flex: 1 }}>
              <div style={{ fontWeight: 800 }}>{r.t}</div>
              <div className="small muted">{r.d}</div>
            </div>
          </div>
        ))}
      </div>

      <div className="stack" style={{ marginTop: 16 }}>
        <button className="btn btn-ink btn-block" onClick={() => notify('Диагностика: модуль «Git» — 6 вопросов, ~10 мин')}>Пройти модуль «Git»</button>
        <button className="btn btn-ghost btn-block" onClick={() => notify('Правила: повтор через 14 дней, пересмотр практики — по запросу')}>Правила повторной попытки</button>
      </div>
    </div>
  )
}

function Applications() {
  const { applications, openOffer, go } = useStore()
  if (applications.length === 0) {
    return (
      <div className="section">
        <div className="card">
          <p className="muted">Заявок пока нет.</p>
          <button className="btn btn-ink" style={{ marginTop: 12 }} onClick={() => go('career', 'offers')}>Смотреть практики</button>
        </div>
      </div>
    )
  }
  return (
    <div className="section stack" style={{ gap: 12 }}>
      {applications.map(a => {
        const o = offers.find(x => x.id === a.offerId)!
        return (
          <button key={a.id} className="card pressable" onClick={() => openOffer(o.id)}>
            <div className="row between">
              <span className="eyebrow muted">{o.company}</span>
              <AppStatusBadge status={a.status} />
            </div>
            <div className="display h3" style={{ marginTop: 8 }}>{o.title}</div>
            {a.status !== 'Отозвана' && <div style={{ marginTop: 14 }}><StatusSteps status={a.status} /></div>}
            <div className="divider" />
            <div className="stack" style={{ gap: 6 }}>
              {[...a.history].reverse().map((h, i) => (
                <div key={i} className="row between small">
                  <span className="row" style={{ gap: 8 }}>
                    <span style={{ width: 8, height: 8, borderRadius: 8, background: i === 0 ? 'var(--ink)' : 'var(--line)' }} />
                    {h.status}
                  </span>
                  <span className="muted">{h.date}</span>
                </div>
              ))}
            </div>
            <div className="row small muted" style={{ marginTop: 10, gap: 6 }}>
              <Icon name={a.withResults ? 'check' : 'lock'} size={14} />
              {a.withResults ? 'Результаты диагностики приложены' : 'Без результатов диагностики'}
            </div>
          </button>
        )
      })}
    </div>
  )
}
