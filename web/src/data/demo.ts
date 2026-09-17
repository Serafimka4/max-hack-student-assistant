// Демонстрационные данные. В интерфейсе они помечены бейджем «Демо-данные».

export type Accent = 'lime' | 'blue' | 'coral'

export const student = {
  name: 'Даниил',
  group: 'б-ИФСТ-21',
  faculty: 'Институт прикладных информационных технологий',
  course: 3,
}

export type LessonKind = 'Лекция' | 'Практика' | 'Лаба'

export type Lesson = {
  id: string
  num: number
  start: string
  end: string
  title: string
  kind: LessonKind
  teacher: string
  room: string
  online?: boolean
}

export type Day = {
  key: string
  short: string
  date: number
  lessons: Lesson[]
}

export const week = {
  parity: 'Числитель',
  range: '15–20 сентября',
  source: 'Учебный отдел ИнПИТ, импорт CSV',
  updated: '16 сент., 18:40',
}

export const days: Day[] = [
  {
    key: 'mon', short: 'Пн', date: 15, lessons: [
      { id: 'l1', num: 1, start: '08:00', end: '09:30', title: 'Базы данных', kind: 'Лекция', teacher: 'Кузнецова М. А.', room: '1/405' },
      { id: 'l2', num: 2, start: '09:45', end: '11:15', title: 'Базы данных', kind: 'Лаба', teacher: 'Кузнецова М. А.', room: '1/312' },
    ],
  },
  {
    key: 'tue', short: 'Вт', date: 16, lessons: [
      { id: 'l3', num: 3, start: '11:35', end: '13:05', title: 'Теория вероятностей', kind: 'Лекция', teacher: 'Орлов П. В.', room: '2/201' },
    ],
  },
  {
    key: 'wed', short: 'Ср', date: 17, lessons: [
      { id: 'l4', num: 1, start: '08:00', end: '09:30', title: 'Веб-программирование', kind: 'Лекция', teacher: 'Смирнов А. И.', room: '1/405' },
      { id: 'l5', num: 2, start: '09:45', end: '11:15', title: 'Веб-программирование', kind: 'Лаба', teacher: 'Смирнов А. И.', room: '1/318' },
      { id: 'l6', num: 3, start: '11:35', end: '13:05', title: 'Английский язык', kind: 'Практика', teacher: 'Белова Е. С.', room: 'Онлайн', online: true },
      { id: 'l7', num: 4, start: '13:45', end: '15:15', title: 'Операционные системы', kind: 'Лекция', teacher: 'Громов Д. Н.', room: '5/110' },
    ],
  },
  {
    key: 'thu', short: 'Чт', date: 18, lessons: [
      { id: 'l8', num: 2, start: '09:45', end: '11:15', title: 'Операционные системы', kind: 'Лаба', teacher: 'Громов Д. Н.', room: '5/214' },
      { id: 'l9', num: 3, start: '11:35', end: '13:05', title: 'Теория вероятностей', kind: 'Практика', teacher: 'Орлов П. В.', room: '2/305' },
    ],
  },
  {
    key: 'fri', short: 'Пт', date: 19, lessons: [
      { id: 'l10', num: 1, start: '08:00', end: '09:30', title: 'Проектная деятельность', kind: 'Практика', teacher: 'Смирнов А. И.', room: '1/220' },
    ],
  },
  { key: 'sat', short: 'Сб', date: 20, lessons: [] },
]

export const todayKey = 'wed'
export const nowLessonId = 'l5'

export type Level = 'none' | 'basic' | 'applied' | 'confident'

export const levelLabel: Record<Level, string> = {
  none: 'Не оценено',
  basic: 'Базовый',
  applied: 'Прикладной',
  confident: 'Уверенный',
}

export type Skill = { id: string; title: string; score: number | null; level: Level }

export const skillProfile = {
  track: 'Frontend-разработка',
  grade: 'Стажёр',
  version: 'v1.2',
  date: '12 сентября',
  practice: 'На проверке' as 'На проверке' | 'Проверено',
  skills: [
    { id: 'html', title: 'HTML / CSS', score: 82, level: 'applied' },
    { id: 'js', title: 'JavaScript', score: 64, level: 'basic' },
    { id: 'api', title: 'HTTP и API', score: 58, level: 'basic' },
    { id: 'git', title: 'Git', score: null, level: 'none' },
  ] as Skill[],
}

export type Offer = {
  id: string
  accent: Accent
  company: string
  title: string
  format: string
  period: string
  places: number
  deadline: string
  direction: 'dev' | 'data' | 'design'
  tasks: string[]
  requires: { skill: string; level: Level }[]
  contact: string
  updated: string
}

export const offers: Offer[] = [
  {
    id: 'o1', accent: 'lime', company: 'Волга Софт', title: 'Frontend-стажёр',
    format: 'Гибрид', period: '1 июл — 31 авг', places: 3, deadline: 'до 10 окт', direction: 'dev',
    tasks: ['Вёрстка интерфейсов личного кабинета', 'Интеграция с REST API', 'Исправление ошибок по задачам наставника'],
    requires: [
      { skill: 'HTML / CSS', level: 'applied' },
      { skill: 'JavaScript', level: 'basic' },
      { skill: 'HTTP и API', level: 'basic' },
      { skill: 'Git', level: 'basic' },
    ],
    contact: 'Иванова О. П., центр карьеры', updated: '14 сент.',
  },
  {
    id: 'o2', accent: 'blue', company: 'Саратовэнерго', title: 'Аналитик данных',
    format: 'Офис', period: '1 июл — 15 авг', places: 2, deadline: 'до 1 ноя', direction: 'data',
    tasks: ['Подготовка отчётов в BI', 'SQL-запросы к данным потребления'],
    requires: [
      { skill: 'SQL', level: 'applied' },
      { skill: 'Python', level: 'basic' },
    ],
    contact: 'Иванова О. П., центр карьеры', updated: '11 сент.',
  },
  {
    id: 'o3', accent: 'coral', company: 'Студия «Контур»', title: 'UX/UI-дизайнер',
    format: 'Удалённо', period: 'гибкий график', places: 1, deadline: 'до 20 окт', direction: 'design',
    tasks: ['Прототипы мобильных экранов', 'Юзабилити-интервью'],
    requires: [
      { skill: 'Figma', level: 'applied' },
      { skill: 'UX-исследования', level: 'basic' },
    ],
    contact: 'Иванова О. П., центр карьеры', updated: '9 сент.',
  },
]

export type AppStatus = 'Подана' | 'На рассмотрении' | 'Принят' | 'Отказ' | 'Отозвана'

export type Application = {
  id: string
  offerId: string
  status: AppStatus
  history: { status: AppStatus; date: string }[]
  withResults: boolean
}

export const initialApplications: Application[] = [
  {
    id: 'a1', offerId: 'o2', status: 'На рассмотрении', withResults: false,
    history: [
      { status: 'Подана', date: '8 сент.' },
      { status: 'На рассмотрении', date: '10 сент.' },
    ],
  },
]

export const faqCategories = ['Все', 'Учёба', 'Справки', 'Практика', 'Контакты'] as const

export const faq = [
  { id: 'f1', cat: 'Справки', q: 'Как получить справку об обучении?', a: 'Закажите справку в деканате ИнПИТ (корп. 1, каб. 208) или через личный кабинет СГТУ. Срок изготовления — до 3 рабочих дней.', owner: 'Деканат ИнПИТ', updated: '2 сент.' },
  { id: 'f2', cat: 'Практика', q: 'Можно ли пройти практику в своей компании?', a: 'Да, если деятельность компании соответствует направлению подготовки. Нужен договор о практике и согласование с руководителем практики от кафедры.', owner: 'Центр карьеры', updated: '5 сент.' },
  { id: 'f3', cat: 'Учёба', q: 'Что делать, если пропустил лабораторную?', a: 'Согласуйте с преподавателем время отработки. При уважительной причине приложите подтверждающий документ.', owner: 'Учебный отдел', updated: '1 сент.' },
  { id: 'f4', cat: 'Контакты', q: 'Куда писать по вопросам стипендии?', a: 'В стипендиальную комиссию института через деканат, часы приёма — Пн–Чт, 10:00–16:00.', owner: 'Деканат ИнПИТ', updated: '28 авг.' },
]

export const templates = [
  { id: 't1', title: 'Заявление на практику', meta: 'DOCX · 28 КБ', owner: 'Центр карьеры' },
  { id: 't2', title: 'Дневник практики', meta: 'DOCX · 41 КБ', owner: 'Кафедра ПИТ' },
  { id: 't3', title: 'Заявление на академический отпуск', meta: 'PDF · 112 КБ', owner: 'Деканат ИнПИТ' },
]

export type TicketStatus = 'Новое' | 'В работе' | 'Решено'

export type Ticket = {
  id: string
  title: string
  context: string
  status: TicketStatus
  owner: string
  due: string
}

export const initialTickets: Ticket[] = [
  { id: 'r1', title: 'Нет аудитории у лабораторной', context: 'Расписание · ОС, Чт 2 пара', status: 'В работе', owner: 'Учебный отдел', due: 'до 18 сент.' },
  { id: 'r2', title: 'Шаблон дневника без поля подписи', context: 'Шаблоны · Дневник практики', status: 'Решено', owner: 'Кафедра ПИТ', due: 'закрыто 12 сент.' },
]
