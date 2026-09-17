import { Icon, type IconName } from '../components/Icon'
import { AppStatusBadge, DemoBadge, Logo, StatusSteps } from '../components/ui'
import { days, nowLessonId, offers, skillProfile, student, todayKey, week } from '../data/demo'
import { useStore, type CareerView, type Tab } from '../store'

function Orb() {
  // Графический мотив из макета: синий круг со штриховкой.
  return (
    <svg className="orb" viewBox="0 0 200 200" aria-hidden="true">
      <circle cx="120" cy="80" r="78" fill="var(--blue)" />
      {Array.from({ length: 9 }, (_, i) => (
        <line key={i} x1={0} y1={20 + i * 16} x2={200} y2={80 + i * 16} stroke="var(--lime)" strokeOpacity=".45" strokeWidth="1" />
      ))}
    </svg>
  )
}

const quick: { title: string; hint: string; icon: IconName; cls: string; tab: Tab; view?: CareerView }[] = [
  { title: 'Мои навыки', hint: 'Диагностика frontend', icon: 'target', cls: 'bg-lime', tab: 'career', view: 'skills' },
  { title: 'Практики', hint: `${offers.length} предложения`, icon: 'briefcase', cls: 'bg-blue', tab: 'career', view: 'offers' },
  { title: 'Шаблоны', hint: 'Заявления и дневник', icon: 'file', cls: 'bg-coral', tab: 'help' },
  { title: 'Задать вопрос', hint: 'FAQ и обращения', icon: 'chat', cls: 'card', tab: 'help' },
]

export function Home() {
  const { go, applications, openOffer } = useStore()
  const today = days.find(d => d.key === todayKey)!
  const current = today.lessons.find(l => l.id === nowLessonId)!
  const next = today.lessons[today.lessons.indexOf(current) + 1]
  const app = applications.find(a => a.status !== 'Отозвана')
  const appOffer = app && offers.find(o => o.id === app.offerId)

  return (
    <div className="screen">
      <header className="dark" style={{ paddingBottom: 24 }}>
        <div className="topbar">
          <Logo />
          <button className="icon-btn on-dark" aria-label="Уведомления" style={{ position: 'relative' }}>
            <Icon name="bell" size={19} />
            <span style={{ position: 'absolute', top: 9, right: 10, width: 7, height: 7, borderRadius: 7, background: 'var(--lime)' }} />
          </button>
        </div>

        <div style={{ padding: '14px 16px 0' }}>
          <div className="row wrap" style={{ marginBottom: 14 }}>
            <span className="badge badge-outline-lime">ИнПИТ · {student.group}</span>
            <DemoBadge />
          </div>
          <h1 className="display" style={{ fontSize: 34 }}>Привет,<br />{student.name}</h1>
          <p style={{ color: 'var(--muted-dark)', marginTop: 10 }}>Среда, 17 сентября · {week.parity.toLowerCase()}</p>
        </div>

        {/* Текущая пара */}
        <button className="card-dark pressable lesson-hero" onClick={() => go('schedule')}>
          <Orb />
          <div style={{ position: 'relative' }}>
            <div className="row between">
              <span className="badge badge-lime"><span className="dot" />Сейчас · до {current.end}</span>
              <Icon name="arrow" size={20} />
            </div>
            <div className="row" style={{ alignItems: 'flex-end', gap: 12, marginTop: 34 }}>
              <span className="display" style={{ fontSize: 76, lineHeight: .8 }}>0{current.num}</span>
              <span className="eyebrow" style={{ color: 'var(--lime)', paddingBottom: 4 }}>пара</span>
            </div>
            <div style={{ fontWeight: 800, fontSize: 18, marginTop: 14 }}>{current.title}</div>
            <div className="row small wrap" style={{ color: 'var(--muted-dark)', marginTop: 4, gap: 12 }}>
              <span className="row" style={{ gap: 4 }}><Icon name="pin" size={14} />{current.room}</span>
              <span>{current.kind}</span>
              <span>{current.teacher}</span>
            </div>
            {next && (
              <div className="small" style={{ marginTop: 14, paddingTop: 12, borderTop: '1px solid var(--ink-line)', color: 'var(--muted-dark)' }}>
                Далее в {next.start} — <span style={{ color: '#fff', fontWeight: 600 }}>{next.title}</span>
              </div>
            )}
          </div>
        </button>
      </header>

      {/* Важное уведомление */}
      <div className="section">
        <div className="card row" style={{ gap: 12, alignItems: 'flex-start', borderColor: 'var(--ink)' }}>
          <span className="icon-btn" style={{ background: 'var(--lime)', flex: 'none' }}><Icon name="alert" size={18} /></span>
          <div>
            <div style={{ fontWeight: 800 }}>Приём заявок на летнюю практику</div>
            <p className="small muted" style={{ marginTop: 2 }}>Подайте заявку до 1 ноября. Без места к контрольной дате координатор предложит варианты.</p>
          </div>
        </div>
      </div>

      {/* Заявка на практику */}
      <div className="section">
        <div className="section-head">
          <h2 className="display h2">Моя практика</h2>
          <button className="link" onClick={() => go('career', 'applications')}>Все заявки <Icon name="right" size={14} /></button>
        </div>
        {app && appOffer ? (
          <button className="card pressable" onClick={() => openOffer(appOffer.id)}>
            <div className="row between">
              <span className="eyebrow muted">{appOffer.company}</span>
              <AppStatusBadge status={app.status} />
            </div>
            <div className="display h3" style={{ marginTop: 8 }}>{appOffer.title}</div>
            <div className="small muted" style={{ marginTop: 2 }}>Обновлено {app.history.at(-1)!.date}</div>
            <div style={{ marginTop: 14 }}><StatusSteps status={app.status} /></div>
          </button>
        ) : (
          <div className="card">
            <p className="muted small">Активных заявок нет.</p>
            <button className="btn btn-ink" style={{ marginTop: 12 }} onClick={() => go('career', 'offers')}>Выбрать практику</button>
          </div>
        )}
      </div>

      {/* Быстрые действия */}
      <div className="section">
        <div className="section-head"><h2 className="display h2">Быстро</h2></div>
        <div className="quick-grid">
          {quick.map((q, i) => (
            <button key={q.title} className={`${q.cls === 'card' ? 'card' : `card-accent ${q.cls}`} pressable quick`} onClick={() => go(q.tab, q.view)}>
              <div className="row between">
                <span className="eyebrow">0{i + 1}</span>
                <Icon name={q.icon} size={20} />
              </div>
              <div>
                <div className="display" style={{ fontSize: 14 }}>{q.title}</div>
                <div className="tiny" style={{ opacity: .75, marginTop: 4 }}>{q.hint}</div>
              </div>
            </button>
          ))}
        </div>
      </div>

      {/* Навыки */}
      <div className="section">
        <button className="card-dark pressable" style={{ padding: 16 }} onClick={() => go('career', 'skills')}>
          <div className="row between">
            <span className="eyebrow" style={{ color: 'var(--lime)' }}>{skillProfile.track}</span>
            <Icon name="arrow" size={18} />
          </div>
          <div className="row" style={{ gap: 14, marginTop: 12 }}>
            {skillProfile.skills.map(s => (
              <div key={s.id} style={{ flex: 1 }}>
                <div className={`meter ${s.score === null ? 'hatched' : ''}`}><i style={{ width: `${s.score ?? 0}%` }} /></div>
                <div className="tiny" style={{ color: 'var(--muted-dark)', marginTop: 6, whiteSpace: 'nowrap', overflow: 'hidden', textOverflow: 'ellipsis' }}>{s.title}</div>
              </div>
            ))}
          </div>
          <div className="small" style={{ marginTop: 12, color: 'var(--muted-dark)' }}>
            Предварительно: <span style={{ color: '#fff', fontWeight: 700 }}>{skillProfile.grade}</span> · практика {skillProfile.practice.toLowerCase()}
          </div>
        </button>
      </div>
    </div>
  )
}
