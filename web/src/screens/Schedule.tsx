import { useState } from 'react'
import { Icon } from '../components/Icon'
import { PageHeader } from '../components/ui'
import { days, nowLessonId, student, todayKey, week, type Lesson } from '../data/demo'
import { useStore } from '../store'

const kindTone: Record<Lesson['kind'], string> = {
  'Лекция': 'badge-soft',
  'Практика': 'badge-blue',
  'Лаба': 'badge-coral',
}

export function Schedule() {
  const { openReport } = useStore()
  const [dayKey, setDayKey] = useState(todayKey)
  const day = days.find(d => d.key === dayKey)!

  return (
    <div className="screen">
      <PageHeader
        eyebrow={<span className="badge badge-outline-lime">{week.parity} · {week.range}</span>}
        title={<>Распи&shy;сание</>}
        right={
          <button className="btn btn-ghost-dark" style={{ minHeight: 36, padding: '0 12px', fontSize: 13, whiteSpace: 'nowrap', flex: 'none' }}>
            {student.group}<Icon name="down" size={16} />
          </button>
        }
      />

      <div className="dark" style={{ padding: '0 16px 18px' }}>
        <div className="day-strip" role="tablist" aria-label="Дни недели">
          {days.map(d => (
            <button key={d.key} role="tab" aria-selected={d.key === dayKey}
              className={`day ${d.key === dayKey ? 'active' : ''}`} onClick={() => setDayKey(d.key)}>
              <span className="tiny">{d.short}</span>
              <span className="display" style={{ fontSize: 17 }}>{d.date}</span>
              <span className="day-dots">{d.lessons.map(l => <i key={l.id} />)}</span>
              {d.key === todayKey && <span className="day-today">сегодня</span>}
            </button>
          ))}
        </div>
      </div>

      <div className="section">
        {day.lessons.length === 0 ? (
          <div className="card" style={{ textAlign: 'center', padding: 32 }}>
            <div className="display h2">Пар нет</div>
            <p className="muted small" style={{ marginTop: 6 }}>Свободный день по расписанию группы.</p>
          </div>
        ) : (
          <div className="stack">
            {day.lessons.map(l => {
              const now = l.id === nowLessonId && dayKey === todayKey
              return (
                <div key={l.id} className={`lesson ${now ? 'now' : ''}`}>
                  <div className="lesson-time">
                    <span className="display" style={{ fontSize: 15 }}>{l.start}</span>
                    <span className="tiny muted">{l.end}</span>
                  </div>
                  <div className="card lesson-card">
                    <div className="row between">
                      <div className="row" style={{ gap: 6 }}>
                        <span className="eyebrow muted">{l.num} пара</span>
                        {now && <span className="badge badge-ink"><span className="dot" style={{ color: 'var(--lime)' }} />Идёт</span>}
                      </div>
                      <span className={`badge ${kindTone[l.kind]}`}>{l.kind}</span>
                    </div>
                    <div style={{ fontWeight: 800, fontSize: 16, marginTop: 8 }}>{l.title}</div>
                    <div className="row small muted wrap" style={{ marginTop: 4, gap: 12 }}>
                      <span className="row" style={{ gap: 4 }}><Icon name={l.online ? 'video' : 'pin'} size={14} />{l.room}</span>
                      <span>{l.teacher}</span>
                    </div>
                    <button className="link muted" style={{ marginTop: 10, fontWeight: 600 }}
                      onClick={() => openReport({
                        title: 'Ошибка в расписании',
                        context: `${l.title}, ${day.short} ${l.num} пара · ${student.group}`,
                        owner: 'Учебный отдел',
                      })}>
                      <Icon name="alert" size={14} />Сообщить об ошибке
                    </button>
                  </div>
                </div>
              )
            })}
          </div>
        )}

        <div className="row small muted" style={{ marginTop: 18, gap: 8, alignItems: 'flex-start' }}>
          <span style={{ marginTop: 2 }}><Icon name="clock" size={15} /></span>
          <span>Источник: {week.source}. Обновлено {week.updated}.</span>
        </div>
      </div>
    </div>
  )
}
