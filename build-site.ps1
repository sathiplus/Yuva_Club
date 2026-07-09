$ErrorActionPreference = "Stop"

$siteRoot = Split-Path -Parent $MyInvocation.MyCommand.Path
$pagesDir = Join-Path $siteRoot "pages"
$assetsDir = Join-Path $siteRoot "assets"
New-Item -ItemType Directory -Path $pagesDir -Force | Out-Null
New-Item -ItemType Directory -Path $assetsDir -Force | Out-Null
Get-ChildItem -Path $pagesDir -Filter *.html -ErrorAction SilentlyContinue | Remove-Item -Force

$itemsJson = @'
[
  {
    "slug": "bhishma-vow",
    "month": "Month 1",
    "category": "Mahabharata",
    "type": "Story",
    "title": "Bhishma's Great Vow",
    "subtitle": "A story about sacrifice, promises, and the cost of duty.",
    "readTime": "8 min",
    "skill": "Responsibility",
    "vocabulary": ["vow", "duty", "sacrifice", "kingdom", "commitment"],
    "reading": ["King Shantanu loved his son Devavrata and knew he would become a wise ruler. One day the king wished to marry Satyavati, but her father asked that Satyavati's future children should inherit the throne. Devavrata saw his father's sadness and made a life-changing promise.", "He gave up his claim to the kingdom and vowed never to marry, so no child of his would compete for the throne. The heavens honored his courage, and Devavrata became known as Bhishma, the one who made a terrible and powerful vow.", "Bhishma's promise protected his father's happiness, but it also shaped the future of Hastinapura. His story asks children to think carefully before making promises and to understand that leadership often includes sacrifice."],
    "questions": ["Why did Devavrata make such a difficult promise?", "Can a promise be noble and still have painful consequences?", "What promises should a young leader take seriously?"],
    "activity": "Ask the presenter to share one promise children can practice at home or school for one week."
  },
  {
    "slug": "pandavas-childhood",
    "month": "Month 1",
    "category": "Mahabharata",
    "type": "Story",
    "title": "The Pandavas Grow Up",
    "subtitle": "Five brothers learn courage, teamwork, and self-control.",
    "readTime": "7 min",
    "skill": "Teamwork",
    "vocabulary": ["brotherhood", "training", "patience", "talent", "jealousy"],
    "reading": ["Yudhishthira, Bhima, Arjuna, Nakula, and Sahadeva grew up in Hastinapura with their cousins, the Kauravas. Each brother had a different strength. Yudhishthira valued truth, Bhima was strong, Arjuna focused deeply, and the twins were graceful and loyal.", "Their teacher Drona trained all the princes in discipline and skill. Arjuna became famous for concentration, while Bhima protected his brothers with courage. But jealousy grew in Duryodhana's heart when he saw the Pandavas loved by the people.", "The childhood of the Pandavas teaches that every team needs different strengths. A good leader does not need to be best at everything. A good leader helps every person use their gift for the common good."],
    "questions": ["Which Pandava quality is most useful for a student leader?", "How can jealousy hurt a team?", "How can friends celebrate each other's strengths?"],
    "activity": "Have each student name one strength they bring to a group project."
  },
  {
    "slug": "arjuna-focus",
    "month": "Month 1",
    "category": "Mahabharata",
    "type": "Story",
    "title": "Arjuna and the Eye of the Bird",
    "subtitle": "A classic lesson in focus and preparation.",
    "readTime": "6 min",
    "skill": "Focus",
    "vocabulary": ["concentration", "target", "practice", "discipline", "attention"],
    "reading": ["One day Guru Drona placed a wooden bird on a tree and asked his students to aim at its eye. Before each prince released the arrow, Drona asked what he could see. Some saw the tree, the leaves, the sky, and the bird.", "When Arjuna's turn came, he said he could see only the eye of the bird. Drona knew Arjuna was ready. Arjuna's success came not from luck, but from complete attention and years of practice.", "This story is perfect for students learning public speaking. When it is your turn to present, focus on the message, prepare well, and let distractions become quiet."],
    "questions": ["What made Arjuna different from the other students?", "How can focus help in reading or speaking?", "What distractions should we reduce before Zoom class?"],
    "activity": "Practice a 30-second focused reading with camera on, voice clear, and eyes steady."
  },
  {
    "slug": "draupadi-courage",
    "month": "Month 1",
    "category": "Mahabharata",
    "type": "Story",
    "title": "Draupadi's Courage",
    "subtitle": "A story about dignity, questions, and moral courage.",
    "readTime": "8 min",
    "skill": "Speaking Up",
    "vocabulary": ["dignity", "justice", "courage", "assembly", "question"],
    "reading": ["Draupadi was known for intelligence and courage. During the dice game, she was treated unfairly in the royal court. Many powerful people stayed silent, but Draupadi did not lose her voice.", "She asked a sharp question: could someone who had already lost himself have the right to stake another person? Her question shook the court because it challenged the adults to think about justice, not just rules.", "Draupadi's story teaches that leadership is not always about being loud. Sometimes leadership begins with one honest question spoken with courage."],
    "questions": ["Why was Draupadi's question powerful?", "When should a student speak up respectfully?", "What is the difference between arguing and asking for justice?"],
    "activity": "Students create one respectful question they can ask when something feels unfair."
  },
  {
    "slug": "krishna-arjuna-gita",
    "month": "Month 1",
    "category": "Mahabharata",
    "type": "Story",
    "title": "Krishna Guides Arjuna",
    "subtitle": "A lesson in duty, confusion, and wise guidance.",
    "readTime": "8 min",
    "skill": "Decision Making",
    "vocabulary": ["dharma", "guidance", "choice", "courage", "wisdom"],
    "reading": ["At Kurukshetra, Arjuna saw teachers, relatives, and friends on both sides of the battlefield. His hands trembled. He did not want to fight and felt confused about what was right.", "Krishna did not simply order Arjuna. He helped Arjuna think. He taught about duty, self-control, action without selfishness, and remembering the larger purpose. The Bhagavad Gita begins in a moment of confusion and becomes a guide for clear thinking.", "Children can learn that even brave people feel unsure. A leader asks for guidance, listens carefully, and chooses what is right with a calm mind."],
    "questions": ["Why did Arjuna feel confused?", "Who gives you wise guidance?", "How can we make choices without selfishness?"],
    "activity": "Make a three-step decision chart: pause, ask, choose."
  },
  {
    "slug": "rama-exile",
    "month": "Month 2",
    "category": "Ramayana",
    "type": "Story",
    "title": "Rama Accepts Exile",
    "subtitle": "A story about duty, calmness, and honoring a promise.",
    "readTime": "7 min",
    "skill": "Integrity",
    "vocabulary": ["exile", "promise", "integrity", "calm", "obedience"],
    "reading": ["Prince Rama was loved in Ayodhya and was ready to become king. But Queen Kaikeyi asked King Dasharatha to fulfill two old promises: Rama should go to the forest for fourteen years, and Bharata should become king.", "Rama could have become angry, but he stayed calm. He accepted exile because he valued his father's word and the peace of the kingdom. Sita and Lakshmana chose to go with him out of love and loyalty.", "Rama's response teaches that integrity means doing the right thing even when it is difficult. For children, this can mean telling the truth, keeping commitments, and staying calm when plans change."],
    "questions": ["Why did Rama accept exile?", "Is calmness a leadership strength?", "How do we react when something feels unfair?"],
    "activity": "Presenter shares one example of staying calm during disappointment."
  },
  {
    "slug": "sita-strength",
    "month": "Month 2",
    "category": "Ramayana",
    "type": "Story",
    "title": "Sita's Strength",
    "subtitle": "A lesson in courage, patience, and inner dignity.",
    "readTime": "7 min",
    "skill": "Resilience",
    "vocabulary": ["resilience", "dignity", "patience", "faith", "courage"],
    "reading": ["Sita chose to join Rama in the forest because she believed family should face hardship together. Later, when Ravana took her to Lanka, she stayed strong and refused to give up her values.", "She did not have an army or weapons, but she had inner strength. She remembered Rama, protected her dignity, and did not let fear control her choices.", "Sita's story helps children understand that courage is not only physical. Courage can be patience, self-respect, and staying true to what is right."],
    "questions": ["What kind of strength did Sita show?", "How can patience be brave?", "What helps you stay strong during a hard time?"],
    "activity": "Students write one sentence: Inner strength means..."
  },
  {
    "slug": "hanuman-leap",
    "month": "Month 2",
    "category": "Ramayana",
    "type": "Story",
    "title": "Hanuman's Leap to Lanka",
    "subtitle": "A joyful story about remembering your own power.",
    "readTime": "7 min",
    "skill": "Confidence",
    "vocabulary": ["confidence", "devotion", "mission", "humility", "strength"],
    "reading": ["When the search for Sita reached the ocean, the vanaras wondered who could cross it. Hanuman had forgotten the full measure of his strength. His friends reminded him of who he was.", "With faith in Rama and confidence in his mission, Hanuman grew mighty and leaped across the ocean to Lanka. He found Sita, gave her Rama's message, and returned with hope for everyone.", "Hanuman teaches that confidence is not showing off. True confidence comes when we use our gifts to serve others."],
    "questions": ["Why did Hanuman need encouragement?", "How can friends help us remember our strengths?", "What is the difference between confidence and pride?"],
    "activity": "Each student gives one encouraging sentence to another student."
  },
  {
    "slug": "bharata-sandals",
    "month": "Month 2",
    "category": "Ramayana",
    "type": "Story",
    "title": "Bharata and Rama's Sandals",
    "subtitle": "A quiet leadership story about humility and trust.",
    "readTime": "6 min",
    "skill": "Humility",
    "vocabulary": ["humility", "trust", "throne", "loyalty", "service"],
    "reading": ["When Bharata learned that Rama had been sent to the forest, he was heartbroken. He did not want a kingdom gained through unfairness. He went to Rama and begged him to return.", "Rama chose to honor his father's promise, so Bharata placed Rama's sandals on the throne and ruled only as caretaker. He showed that leadership is service, not ownership.", "Bharata's story reminds children that a good leader does not grab attention. A good leader protects what is right, even when no one is clapping."],
    "questions": ["Why did Bharata refuse to enjoy the throne?", "What does it mean to be a caretaker leader?", "How can humility make a team stronger?"],
    "activity": "Discuss one classroom job where service matters more than praise."
  },
  {
    "slug": "vivekananda-childhood",
    "month": "Month 3",
    "category": "Swami Vivekananda",
    "type": "Person",
    "title": "Young Narendra",
    "subtitle": "The curious child who became Swami Vivekananda.",
    "readTime": "7 min",
    "skill": "Curiosity",
    "vocabulary": ["curiosity", "meditation", "service", "fearless", "learning"],
    "reading": ["Before he became Swami Vivekananda, he was Narendranath Datta, a bright and energetic child in Kolkata. He loved music, exercise, studies, and deep questions about God and life.", "Narendra was not satisfied with easy answers. He wanted truth through experience. His courage to ask questions helped him meet Sri Ramakrishna, who guided his spiritual journey.", "Young Narendra teaches children that questions are not a weakness. Honest curiosity can become the beginning of wisdom."],
    "questions": ["Why is asking questions important?", "How can curiosity be respectful?", "What question would you ask a wise teacher?"],
    "activity": "Presenter collects three thoughtful questions from the group."
  },
  {
    "slug": "vivekananda-chicago",
    "month": "Month 3",
    "category": "Swami Vivekananda",
    "type": "Person",
    "title": "The Chicago Speech",
    "subtitle": "A lesson in public speaking and respect for all.",
    "readTime": "7 min",
    "skill": "Public Speaking",
    "vocabulary": ["respect", "audience", "confidence", "harmony", "message"],
    "reading": ["In 1893, Swami Vivekananda spoke at the World's Parliament of Religions in Chicago. He represented India's spiritual heritage and began with warmth toward the audience. His message emphasized respect, acceptance, and harmony.", "He did not speak to defeat others. He spoke to build understanding. His voice was powerful because it carried both confidence and compassion.", "For Yuva Club, this story is a model for presentations: know your message, respect your listeners, and speak from the heart."],
    "questions": ["Why did Vivekananda's speech inspire people?", "How can a speaker show respect?", "What makes a speech memorable?"],
    "activity": "Students practice a 20-second opening greeting for a speech."
  },
  {
    "slug": "vivekananda-service",
    "month": "Month 3",
    "category": "Swami Vivekananda",
    "type": "Person",
    "title": "Service as Leadership",
    "subtitle": "Vivekananda's call to serve people with strength.",
    "readTime": "7 min",
    "skill": "Service",
    "vocabulary": ["service", "education", "strength", "compassion", "mission"],
    "reading": ["Swami Vivekananda traveled across India and saw the struggles of ordinary people. He believed education, strength, and self-confidence were necessary for national upliftment.", "He founded the Ramakrishna Mission to combine spiritual ideals with practical service such as education, relief work, and care for people in need.", "His lesson for children is simple and powerful: leadership is not only speaking well. Leadership is using your energy to help others rise."],
    "questions": ["Why is service a leadership quality?", "How can students serve at home or school?", "What does strength with compassion look like?"],
    "activity": "Plan one small service action for the week."
  },
  {
    "slug": "kalam-childhood",
    "month": "Month 4",
    "category": "Dr. A.P.J. Abdul Kalam",
    "type": "Person",
    "title": "Kalam's Early Life",
    "subtitle": "A humble beginning filled with hard work and dreams.",
    "readTime": "7 min",
    "skill": "Perseverance",
    "vocabulary": ["perseverance", "humble", "education", "dream", "effort"],
    "reading": ["A.P.J. Abdul Kalam was born in Rameswaram, Tamil Nadu. His family was not wealthy, and as a young boy he helped by delivering newspapers. He studied with dedication and kept learning even when life was difficult.", "Kalam's childhood reminds students that success does not require a perfect beginning. It requires effort, discipline, teachers, family support, and a dream that keeps growing.", "His life is especially inspiring for children because he loved speaking with students and believed young minds could build the future."],
    "questions": ["What challenges did Kalam face as a child?", "How can hard work change a life?", "What dream would you like to work toward?"],
    "activity": "Students write one dream and one habit needed for it."
  },
  {
    "slug": "kalam-scientist",
    "month": "Month 4",
    "category": "Dr. A.P.J. Abdul Kalam",
    "type": "Person",
    "title": "Kalam the Scientist",
    "subtitle": "A story about teamwork, failure, and innovation.",
    "readTime": "7 min",
    "skill": "Innovation",
    "vocabulary": ["innovation", "rocket", "teamwork", "failure", "mission"],
    "reading": ["Dr. Kalam worked with India's space and defense programs. Scientific work requires patience because experiments do not always succeed the first time. Kalam learned from failures and kept improving with his teams.", "He contributed to important projects in ISRO and DRDO and became known for inspiring scientific confidence in India. His success was not only personal; it came from teams working toward a national mission.", "For students, Kalam's scientific life teaches that mistakes can become lessons when we stay honest, curious, and determined."],
    "questions": ["Why is failure important in science?", "How did teamwork help Kalam?", "How can students respond to mistakes?"],
    "activity": "Presenter shares one invention idea that could help the community."
  },
  {
    "slug": "kalam-president",
    "month": "Month 4",
    "category": "Dr. A.P.J. Abdul Kalam",
    "type": "Person",
    "title": "The People's President",
    "subtitle": "A leader remembered for humility and love for students.",
    "readTime": "7 min",
    "skill": "Vision",
    "vocabulary": ["vision", "president", "humility", "youth", "inspiration"],
    "reading": ["Dr. Kalam served as the President of India from 2002 to 2007. Even in a high office, he remained simple, approachable, and deeply connected with students.", "He encouraged children to dream big and work for a developed, peaceful, and innovative India. His books and speeches often focused on courage, character, and national service.", "Kalam's leadership shows that greatness can be gentle. A true leader lifts young people and helps them believe they can contribute."],
    "questions": ["Why was Kalam called the People's President?", "How can humility and vision go together?", "What kind of future would you like to help build?"],
    "activity": "Students give a one-minute 'My dream for the future' talk."
  },
  {
    "slug": "panchatantra-mongoose",
    "month": "Month 5",
    "category": "Panchatantra",
    "type": "Story",
    "title": "The Loyal Mongoose",
    "subtitle": "Think before acting in anger or fear.",
    "readTime": "6 min",
    "skill": "Critical Thinking",
    "vocabulary": ["loyal", "hasty", "evidence", "regret", "protect"],
    "reading": ["A family had a loyal mongoose who cared for their baby. One day, while the parents were away, a snake entered the house. The mongoose fought the snake and saved the child.", "When the mother returned, she saw blood on the mongoose's mouth and assumed the worst. In fear and anger, she punished the mongoose before checking the baby. Then she discovered the child was safe and the snake was dead.", "The lesson is clear: do not act before understanding the truth. Leaders look for evidence before making decisions."],
    "questions": ["What mistake did the mother make?", "Why is evidence important?", "How can we pause before reacting?"],
    "activity": "Practice saying: pause, check, then choose."
  },
  {
    "slug": "panchatantra-lion-hare",
    "month": "Month 5",
    "category": "Panchatantra",
    "type": "Story",
    "title": "The Lion and the Clever Hare",
    "subtitle": "Wisdom can be stronger than force.",
    "readTime": "6 min",
    "skill": "Problem Solving",
    "vocabulary": ["clever", "strategy", "force", "wisdom", "danger"],
    "reading": ["A fierce lion frightened all the animals in the forest. The animals agreed to send one animal each day so the lion would stop hunting everyone. One day a small hare was chosen.", "The hare used intelligence instead of strength. He led the lion to a well and told him another lion lived inside. Seeing his own reflection, the angry lion jumped in and was defeated by his own pride.", "This story teaches children that calm thinking can solve problems that strength alone cannot."],
    "questions": ["How did the hare solve the problem?", "Why did pride defeat the lion?", "When is strategy better than force?"],
    "activity": "Students solve a simple problem using three possible strategies."
  },
  {
    "slug": "panchatantra-monkey-crocodile",
    "month": "Month 5",
    "category": "Panchatantra",
    "type": "Story",
    "title": "The Monkey and the Crocodile",
    "subtitle": "Choose friends wisely and think quickly.",
    "readTime": "6 min",
    "skill": "Wise Choices",
    "vocabulary": ["friendship", "trust", "betrayal", "quick thinking", "choice"],
    "reading": ["A monkey lived on a fruit tree by the river and shared sweet fruits with a crocodile. The crocodile's wife wanted the monkey's heart, and the crocodile sadly agreed to trick his friend.", "When the monkey learned the truth in the middle of the river, he stayed calm. He said he had left his heart on the tree. The crocodile took him back, and the monkey escaped.", "The story reminds children that friendship needs trust. It also teaches quick thinking during danger."],
    "questions": ["What made the friendship break?", "How did the monkey stay calm?", "What qualities make a trustworthy friend?"],
    "activity": "Make a list of three signs of a good friend."
  },
  {
    "slug": "panchatantra-tortoise-geese",
    "month": "Month 5",
    "category": "Panchatantra",
    "type": "Story",
    "title": "The Talkative Tortoise",
    "subtitle": "Self-control can protect us from trouble.",
    "readTime": "6 min",
    "skill": "Self-Control",
    "vocabulary": ["self-control", "advice", "silence", "warning", "consequence"],
    "reading": ["A tortoise had two geese friends. When their lake dried up, the geese found a plan. The tortoise would hold a stick in his mouth while the geese carried the ends. The only rule was that the tortoise must not speak.", "As they flew, people below laughed and shouted. The tortoise became angry and opened his mouth to answer. He fell because he forgot the advice that could save him.", "This story teaches that words have power. A leader knows when to speak and when silence is wiser."],
    "questions": ["Why did the tortoise fail?", "When is silence helpful?", "How can students control the urge to interrupt?"],
    "activity": "Practice listening for one full minute before responding."
  },
  {
    "slug": "chanakya",
    "month": "Month 6",
    "category": "Great Indians",
    "type": "Person",
    "title": "Chanakya",
    "subtitle": "A strategist who valued planning and discipline.",
    "readTime": "6 min",
    "skill": "Strategic Thinking",
    "vocabulary": ["strategy", "discipline", "teacher", "planning", "statecraft"],
    "reading": ["Chanakya is remembered as a teacher, thinker, and strategist from ancient India. He guided Chandragupta Maurya and is associated with ideas about leadership, governance, and careful planning.", "His story can help children understand that leadership requires preparation. A leader thinks beyond today and asks how choices will affect people tomorrow.", "Chanakya's lesson for students is to plan wisely, learn deeply, and use knowledge responsibly."],
    "questions": ["Why does planning matter?", "How can knowledge become responsibility?", "What is one goal that needs a plan?"],
    "activity": "Create a three-step plan for a personal goal."
  },
  {
    "slug": "aryabhata",
    "month": "Month 6",
    "category": "Great Indians",
    "type": "Person",
    "title": "Aryabhata",
    "subtitle": "A mathematician and astronomer who looked at the sky with wonder.",
    "readTime": "6 min",
    "skill": "Inquiry",
    "vocabulary": ["astronomy", "mathematics", "observation", "calculation", "wonder"],
    "reading": ["Aryabhata was one of India's great mathematicians and astronomers. He studied numbers, time, movement, and the sky with careful observation and reasoning.", "His work reminds children that curiosity can become discovery when it is joined with practice. Math is not only homework; it is a language for understanding patterns in the world.", "Aryabhata's leadership lesson is intellectual courage: ask big questions and respect careful thinking."],
    "questions": ["What does the sky make you wonder about?", "How can math help us understand life?", "Why should leaders respect facts?"],
    "activity": "Students ask one big science or math question."
  },
  {
    "slug": "rani-lakshmibai",
    "month": "Month 6",
    "category": "Great Indians",
    "type": "Person",
    "title": "Rani Lakshmibai",
    "subtitle": "A queen remembered for courage and love for her people.",
    "readTime": "6 min",
    "skill": "Courage",
    "vocabulary": ["queen", "courage", "freedom", "responsibility", "bravery"],
    "reading": ["Rani Lakshmibai of Jhansi is remembered as a brave queen who stood up during a difficult period in Indian history. She became a symbol of courage, responsibility, and love for her kingdom.", "Her story teaches that courage is not the absence of fear. Courage is doing what duty requires even when the situation is hard.", "For children, her life can inspire confidence, especially for girls learning that leadership belongs to them too."],
    "questions": ["What makes Rani Lakshmibai inspiring?", "Can courage be calm as well as bold?", "How can students show courage respectfully?"],
    "activity": "Give a 30-second courage statement beginning with: I can stand up for..."
  },
  {
    "slug": "subhas-chandra-bose",
    "month": "Month 6",
    "category": "Great Indians",
    "type": "Person",
    "title": "Subhas Chandra Bose",
    "subtitle": "A leader with determination and a powerful call for freedom.",
    "readTime": "6 min",
    "skill": "Determination",
    "vocabulary": ["freedom", "determination", "sacrifice", "courage", "nation"],
    "reading": ["Subhas Chandra Bose was a major figure in India's freedom movement. He is remembered for determination, bold leadership, and a deep desire to see India free.", "His life invites discussion about sacrifice, courage, and different ways people work toward a goal. Students can learn that leadership often requires commitment larger than personal comfort.", "The student lesson is to work for meaningful goals with discipline and respect for others."],
    "questions": ["What does determination mean?", "Why do big goals require sacrifice?", "How can students work for a good cause?"],
    "activity": "Students identify one meaningful cause they care about."
  },
  {
    "slug": "sardar-patel",
    "month": "Month 6",
    "category": "Great Indians",
    "type": "Person",
    "title": "Sardar Vallabhbhai Patel",
    "subtitle": "A unifier who valued courage and practical leadership.",
    "readTime": "6 min",
    "skill": "Unity",
    "vocabulary": ["unity", "practical", "leader", "nation", "trust"],
    "reading": ["Sardar Vallabhbhai Patel played an important role in uniting India after independence. He is often remembered for firmness, practical judgment, and commitment to national unity.", "His leadership teaches that unity does not happen by accident. It requires patience, communication, trust, and the courage to make difficult decisions.", "For a club, Patel's lesson is powerful: a group becomes strong when everyone feels part of one purpose."],
    "questions": ["Why is unity important?", "How can a leader bring people together?", "What can divide a team, and how can we fix it?"],
    "activity": "Create one group agreement for respectful discussion."
  },
  {
    "slug": "ramanujan",
    "month": "Month 6",
    "category": "Great Indians",
    "type": "Person",
    "title": "Srinivasa Ramanujan",
    "subtitle": "A mathematical genius with imagination and persistence.",
    "readTime": "6 min",
    "skill": "Persistence",
    "vocabulary": ["genius", "mathematics", "pattern", "persistence", "imagination"],
    "reading": ["Srinivasa Ramanujan loved numbers deeply. Even with limited resources, he filled notebooks with mathematical ideas and patterns. His talent eventually reached scholars abroad.", "His life teaches that gifts need persistence. It also reminds us to respect unusual thinkers, because creativity may look different from person to person.", "For children, Ramanujan's story says: keep exploring what fascinates you, and do not be afraid of deep work."],
    "questions": ["Why did Ramanujan keep working on math?", "How can imagination help learning?", "What subject makes you curious?"],
    "activity": "Students share one pattern they notice in daily life."
  },
  {
    "slug": "kalpana-chawla",
    "month": "Month 6",
    "category": "Great Indians",
    "type": "Person",
    "title": "Kalpana Chawla",
    "subtitle": "A dreamer who reached space.",
    "readTime": "6 min",
    "skill": "Dreaming Big",
    "vocabulary": ["astronaut", "space", "dream", "study", "courage"],
    "reading": ["Kalpana Chawla was born in Karnal, India, and became an astronaut. Her journey from a young student fascinated by flight to space traveler inspires children around the world.", "She showed that dreams need study, training, courage, and patience. Her life especially encourages students to explore science and ask what is possible.", "Kalpana's leadership lesson is to dream beyond limits while doing the daily work needed to reach the dream."],
    "questions": ["What dream did Kalpana follow?", "Why do dreams need discipline?", "What would you ask an astronaut?"],
    "activity": "Students draw or describe one place they dream of exploring."
  },
  {
    "slug": "dharma",
    "month": "Month 6",
    "category": "Hindu Traditions",
    "type": "Tradition",
    "title": "Dharma",
    "subtitle": "Doing what is right with care and responsibility.",
    "readTime": "5 min",
    "skill": "Ethical Thinking",
    "vocabulary": ["dharma", "duty", "ethics", "responsibility", "care"],
    "reading": ["Dharma is a rich idea that can mean duty, right conduct, responsibility, and harmony. In stories like the Ramayana and Mahabharata, characters often face difficult dharma questions.", "For children, dharma can begin simply: tell the truth, respect others, help family, study sincerely, and think before acting.", "Dharma is not only a rule. It is a way to ask: what is the right thing to do in this situation?"],
    "questions": ["What does dharma mean in daily life?", "Can doing the right thing be difficult?", "How do stories help us understand choices?"],
    "activity": "Discuss one school situation and identify the dharmic choice."
  },
  {
    "slug": "namaste",
    "month": "Month 6",
    "category": "Hindu Traditions",
    "type": "Tradition",
    "title": "Namaste",
    "subtitle": "A greeting of respect and humility.",
    "readTime": "5 min",
    "skill": "Respect",
    "vocabulary": ["namaste", "respect", "greeting", "humility", "kindness"],
    "reading": ["Namaste is a traditional Indian greeting made with folded hands. It expresses respect and acknowledges the dignity in another person.", "Children can practice namaste not only as a gesture but as an attitude. It means we begin interactions with kindness, attention, and humility.", "In a Zoom club, a namaste greeting can set the tone for respectful listening and thoughtful speaking."],
    "questions": ["What does a respectful greeting do?", "How can body language show kindness?", "How should we greet elders and teachers?"],
    "activity": "Open the session with each child greeting the group clearly."
  },
  {
    "slug": "diwali",
    "month": "Month 6",
    "category": "Hindu Traditions",
    "type": "Festival",
    "title": "Diwali",
    "subtitle": "A festival of light, hope, and good over darkness.",
    "readTime": "5 min",
    "skill": "Hope",
    "vocabulary": ["festival", "light", "hope", "goodness", "gratitude"],
    "reading": ["Diwali is celebrated in many ways across India and the world. Families light lamps, clean homes, share sweets, pray, and gather with loved ones.", "Many children connect Diwali with Rama's return to Ayodhya, while other traditions highlight Lakshmi, Krishna, or regional stories. The common theme is light overcoming darkness.", "For Yuva Club, Diwali can become a lesson in bringing light through kindness, gratitude, and good choices."],
    "questions": ["What does light symbolize?", "How does your family celebrate Diwali?", "How can a child bring light to someone's day?"],
    "activity": "Students share one act of kindness for Diwali season."
  },
  {
    "slug": "holi",
    "month": "Month 6",
    "category": "Hindu Traditions",
    "type": "Festival",
    "title": "Holi",
    "subtitle": "A colorful celebration of joy and renewal.",
    "readTime": "5 min",
    "skill": "Joyful Community",
    "vocabulary": ["Holi", "color", "spring", "renewal", "community"],
    "reading": ["Holi is known as the festival of colors and is celebrated with joy, music, family, and community. It is linked with spring and with stories of devotion and the victory of good over harmful pride.", "The colors of Holi remind us that life is richer when people come together. It is also a time to renew friendships and let go of small conflicts.", "For children, Holi can teach joyful community: celebrate, include others, and begin again with a cheerful heart."],
    "questions": ["Why do festivals bring people together?", "What can colors symbolize?", "How can we include someone who feels left out?"],
    "activity": "Each student names one color and one value it can represent."
  },
  {
    "slug": "guru",
    "month": "Month 6",
    "category": "Hindu Traditions",
    "type": "Tradition",
    "title": "Guru",
    "subtitle": "The teacher who helps remove darkness.",
    "readTime": "5 min",
    "skill": "Learning",
    "vocabulary": ["guru", "teacher", "gratitude", "guidance", "learning"],
    "reading": ["In Indian tradition, a guru is a teacher or guide who helps students learn, grow, and see more clearly. The word is often explained as one who removes darkness through knowledge.", "Children have many teachers: parents, school teachers, music teachers, coaches, elders, and spiritual guides. Respect for teachers is also respect for learning itself.", "The leadership lesson is gratitude. A good student listens, practices, asks questions, and honors those who guide them."],
    "questions": ["Who are your teachers?", "How do we show gratitude to a teacher?", "Why does a leader need guidance?"],
    "activity": "Write a short thank-you message to a teacher or elder."
  },
  {
    "slug": "abraham-lincoln",
    "month": "Month 7",
    "category": "Great American Leaders & Innovators",
    "type": "Person",
    "title": "Abraham Lincoln",
    "subtitle": "A leader remembered for honesty, courage, and unity.",
    "readTime": "7 min",
    "skill": "Honest Leadership",
    "vocabulary": ["honesty", "unity", "courage", "president", "perseverance"],
    "reading": ["Abraham Lincoln was born in a small log cabin in Kentucky in 1809. His family was poor, and he had very little formal education. However, Lincoln loved reading and learning. He borrowed books whenever he could and often studied by candlelight after long days of work.", "As a young man, Lincoln worked many different jobs, including farm laborer, store clerk, and surveyor. He experienced failures in business and lost several elections before eventually becoming a lawyer and entering politics. Rather than giving up, he used each setback as an opportunity to learn and improve.", "In 1860, Lincoln was elected the 16th President of the United States. Soon after, the country entered the Civil War, one of the most difficult periods in American history. Lincoln believed that the United States should remain united and worked tirelessly to preserve the nation.", "One of his most important actions was issuing the Emancipation Proclamation, which declared freedom for enslaved people in Confederate states. Although many people disagreed with his decisions, Lincoln remained committed to doing what he believed was morally right.", "Lincoln was known for listening carefully, treating people respectfully, and making thoughtful decisions. Even when facing criticism, he stayed focused on serving the country rather than seeking personal gain.", "Today, Abraham Lincoln is remembered as a leader who demonstrated honesty, perseverance, humility, and courage. His life teaches us that great leadership is not about power; it is about character and doing the right thing even when it is difficult."],
    "questions": ["What challenges did Lincoln face growing up?", "Why did he keep going after multiple failures?", "What does moral courage mean?", "Can a leader be successful without honesty?", "What can students learn from Lincoln's example?"],
    "activity": "During the coming week, identify one situation where you can choose honesty even when it might be difficult. Be prepared to share your experience with the group."
  },
  {
    "slug": "martin-luther-king-jr",
    "month": "Month 8",
    "category": "Great American Leaders & Innovators",
    "type": "Person",
    "title": "Martin Luther King Jr.",
    "subtitle": "A voice for justice, courage, and peaceful change.",
    "readTime": "7 min",
    "skill": "Peaceful Courage",
    "vocabulary": ["justice", "equality", "peace", "dream", "courage"],
    "reading": ["Dr. Martin Luther King Jr. became one of America's most important civil rights leaders. He believed people should be treated with dignity and fairness, regardless of race.", "He used speeches, marches, and nonviolent action to call for justice. His famous dream was not only personal; it invited the country to become better and fairer for all people.", "For children, Dr. King's life teaches that words can inspire change when they are joined with courage, discipline, and respect for others."],
    "questions": ["How can peaceful action be powerful?", "What makes a speech inspire people?", "What does fairness look like in school or community life?"],
    "activity": "Students give a short 'I have a dream for my community' statement."
  },
  {
    "slug": "benjamin-franklin",
    "month": "Month 9",
    "category": "Great American Leaders & Innovators",
    "type": "Person",
    "title": "Benjamin Franklin",
    "subtitle": "A curious inventor, writer, and civic leader.",
    "readTime": "6 min",
    "skill": "Practical Wisdom",
    "vocabulary": ["invention", "curiosity", "writing", "civic", "wisdom"],
    "reading": ["Benjamin Franklin was a printer, writer, inventor, scientist, and public servant. He loved useful ideas and believed learning should help people live better lives.", "Franklin explored electricity, improved everyday tools, helped build community institutions, and used writing to share advice and ideas. His life shows the power of curiosity connected to practical service.", "Students can learn from Franklin that leadership is not only one role. A curious person can serve through ideas, inventions, writing, and community action."],
    "questions": ["Why is curiosity useful for leadership?", "How can an invention serve people?", "What practical problem would you like to solve?"],
    "activity": "Students sketch or describe one useful invention for home, school, or community."
  },
  {
    "slug": "helen-keller",
    "month": "Month 10",
    "category": "Great American Leaders & Innovators",
    "type": "Person",
    "title": "Helen Keller",
    "subtitle": "A life of perseverance, learning, and advocacy.",
    "readTime": "7 min",
    "skill": "Perseverance",
    "vocabulary": ["perseverance", "communication", "advocacy", "teacher", "courage"],
    "reading": ["Helen Keller lost her sight and hearing as a young child. With the help of her teacher Anne Sullivan, she learned to communicate and opened a new world of language and learning.", "Helen became an author, speaker, and advocate for people with disabilities. Her life showed that obstacles can be met with patience, support, and determination.", "Her story teaches children empathy and perseverance. A leader notices the challenges others face and works to make the world more welcoming."],
    "questions": ["How did Helen Keller show perseverance?", "Why are teachers and helpers important?", "How can we include people with different abilities?"],
    "activity": "Students practice explaining one idea clearly without using their usual method."
  },
  {
    "slug": "wright-brothers",
    "month": "Month 11",
    "category": "Great American Leaders & Innovators",
    "type": "People",
    "title": "The Wright Brothers",
    "subtitle": "Innovation, experiments, and the first powered flight.",
    "readTime": "7 min",
    "skill": "Innovation",
    "vocabulary": ["innovation", "flight", "experiment", "prototype", "persistence"],
    "reading": ["Orville and Wilbur Wright were brothers who wanted to understand flight. They studied birds, built models, tested ideas, and improved their designs step by step.", "Their success did not come from one lucky moment. It came from careful observation, repeated experiments, and learning from what did not work.", "The Wright Brothers teach children that innovation is a process. Good ideas grow when we test, revise, cooperate, and keep learning."],
    "questions": ["Why are experiments important?", "How did teamwork help the Wright Brothers?", "What can failure teach an inventor?"],
    "activity": "Students design a paper-airplane experiment and predict what will improve flight."
  },
  {
    "slug": "leadership-around-us",
    "month": "Month 12",
    "category": "Everyday Leaders",
    "type": "Student Presentation",
    "title": "Everyday Leaders",
    "subtitle": "Student presentation assignment about a real-life hero who inspires you.",
    "readTime": "5 min",
    "skill": "Presentation",
    "vocabulary": ["hero", "community", "kindness", "responsibility", "gratitude", "presentation", "inspiration"],
    "reading": ["Leadership is not found only in famous people. Every day, we meet people who inspire us through kindness, honesty, courage, hard work, service, and responsibility.", "Choose someone who has made a positive difference in your life or community and prepare a 3-5 minute presentation. Your hero could be a parent or grandparent, a teacher or coach, a family member, a volunteer, a community leader, a scientist, entrepreneur, or artist, a friend or classmate, or anyone who inspires you through their actions.", "Your presentation should include: Who is your hero? Why did you choose this person? What challenges have they overcome? What leadership qualities do they demonstrate? What is one important lesson you learned from them? How will you apply that lesson in your own life?", "Presentation tips: speak for 3-5 minutes, use your own words, maintain eye contact, give real-life examples, and be prepared to answer questions from the audience.", "After each presentation, every student should ask at least one thoughtful question. Respect different opinions, listen carefully, and encourage one another.", "Great leaders are not only famous. They are the people who inspire us every day. Learn from them, thank them, and become that kind of leader for someone else."],
    "questions": ["Who is your hero, and why did you choose this person?", "What challenges has your hero overcome?", "What leadership qualities does your hero demonstrate?", "What important lesson did you learn from your hero?", "How will you apply that lesson in your own life?"],
    "activity": "Prepare and give a 3-5 minute presentation about your hero with real-life examples and one lesson you will apply."
  }
]
'@

$items = @()
$parsedItems = ConvertFrom-Json -InputObject $itemsJson
foreach ($parsedItem in $parsedItems) {
  $items += $parsedItem
}
$items = @($items | Where-Object { $_.slug -notin @("benjamin-franklin", "helen-keller") })

foreach ($item in $items) {
  if ($item.slug -in @("kalam-childhood", "kalam-scientist", "kalam-president", "vivekananda-childhood", "vivekananda-chicago", "vivekananda-service", "mahatma-gandhi", "rani-lakshmibai", "subhas-chandra-bose", "sardar-patel", "chanakya", "leadership-around-us")) {
    $item.category = "Great Leaders"
  }
  elseif ($item.slug -in @("abraham-lincoln", "martin-luther-king-jr")) {
    $item.category = "World Leaders"
  }
  elseif ($item.slug -in @("aryabhata", "srinivasa-ramanujan", "wright-brothers", "kalpana-chawla")) {
    $item.category = "Scientists & Inventors"
  }
  elseif ($item.category -eq "Hindu Traditions") {
    $item.category = "Indian Heritage"
  }
  elseif ($item.category -in @("Mahabharata", "Ramayana", "Panchatantra")) {
    $item.category = "Leadership Lessons from Stories"
  }
}

$modernItemsJson = @'
[
  {
    "slug": "steve-jobs",
    "month": "Section 7",
    "category": "Innovators, Entrepreneurs & Changemakers",
    "type": "Person",
    "title": "Steve Jobs",
    "subtitle": "Design, storytelling, failure, and building products people love.",
    "readTime": "7 min",
    "skill": "Vision",
    "vocabulary": ["design", "vision", "prototype", "focus", "resilience"],
    "reading": ["Steve Jobs co-founded Apple and became known for connecting technology with design, simplicity, and storytelling. He believed that products should not only work well, but also feel meaningful and easy for people to use.", "Jobs also faced failure. He was forced out of Apple, the company he helped create. Instead of disappearing, he built new companies, learned new lessons, and later returned to Apple with sharper focus.", "His story helps teenagers discuss creativity, taste, communication, and resilience. It also raises ethical questions: when a leader has a strong vision, how should that leader treat teammates?"],
    "questions": ["Why did design matter so much to Steve Jobs?", "How can failure become a turning point?", "What makes a product meaningful instead of merely useful?"],
    "activity": "Choose one everyday object or app and explain how you would improve its design for users."
  },
  {
    "slug": "elon-musk",
    "month": "Section 7",
    "category": "Innovators, Entrepreneurs & Changemakers",
    "type": "Person",
    "title": "Elon Musk",
    "subtitle": "Risk-taking, ambitious goals, engineering, and public responsibility.",
    "readTime": "7 min",
    "skill": "Calculated Risk",
    "vocabulary": ["risk", "mission", "engineering", "iteration", "responsibility"],
    "reading": ["Elon Musk is associated with companies that aim at very large problems, including electric vehicles, space technology, and digital platforms. His career shows how ambitious ideas can attract talent, investment, criticism, and intense public attention.", "Musk's companies often use rapid testing and iteration. Rockets fail before they succeed, products change after feedback, and teams work under pressure to solve difficult engineering problems.", "His story is useful for teenagers because it invites balanced discussion. Big dreams can inspire innovation, but public leaders also need responsibility, communication, and ethical judgment."],
    "questions": ["What is the difference between a bold risk and a careless risk?", "Why do ambitious goals attract both support and criticism?", "How should innovators think about public responsibility?"],
    "activity": "Pick one big problem and describe one risky but thoughtful solution."
  },
  {
    "slug": "bill-gates",
    "month": "Section 7",
    "category": "Innovators, Entrepreneurs & Changemakers",
    "type": "Person",
    "title": "Bill Gates",
    "subtitle": "Software, scale, philanthropy, and using wealth for impact.",
    "readTime": "7 min",
    "skill": "Long-Term Impact",
    "vocabulary": ["software", "scale", "philanthropy", "strategy", "impact"],
    "reading": ["Bill Gates co-founded Microsoft and helped shape the personal computer era. His work showed how software could become a platform used by millions of people, businesses, and schools.", "Later, Gates became known for philanthropy through global health, education, and poverty-focused work. His story connects entrepreneurship with a question that matters deeply: what should people do with success after they achieve it?", "For teenagers, Gates offers lessons in strategy, learning, scale, and social responsibility. Building something big is one kind of achievement; using resources wisely is another."],
    "questions": ["How did software change the world?", "What does it mean to create impact at scale?", "Why should successful people think about giving back?"],
    "activity": "Design a simple technology idea that could help many people at low cost."
  },
  {
    "slug": "sundar-pichai",
    "month": "Section 7",
    "category": "Innovators, Entrepreneurs & Changemakers",
    "type": "Person",
    "title": "Sundar Pichai",
    "subtitle": "Product leadership, calm communication, and global technology.",
    "readTime": "7 min",
    "skill": "Product Thinking",
    "vocabulary": ["product", "platform", "communication", "scale", "empathy"],
    "reading": ["Sundar Pichai grew up in India and became a technology leader known for product thinking and calm communication. His career is often connected with products that reached enormous numbers of users, including web browsers and mobile technology.", "Product leadership requires listening to users, understanding technical tradeoffs, and helping teams make clear choices. It is not only about having ideas; it is about turning ideas into tools people can actually use.", "For Indian-American teenagers, Pichai's story connects heritage, education, global opportunity, and leadership in a modern career. It also raises questions about how powerful technology companies should serve society responsibly."],
    "questions": ["What does product thinking mean?", "Why is calm communication useful for leaders?", "How should technology leaders think about users and society?"],
    "activity": "Choose one app you use and identify the user problem it solves."
  },
  {
    "slug": "satya-nadella",
    "month": "Section 7",
    "category": "Innovators, Entrepreneurs & Changemakers",
    "type": "Person",
    "title": "Satya Nadella",
    "subtitle": "Growth mindset, empathy, cloud computing, and culture change.",
    "readTime": "7 min",
    "skill": "Growth Mindset",
    "vocabulary": ["empathy", "culture", "cloud", "mindset", "transformation"],
    "reading": ["Satya Nadella grew up in India and became known for leading Microsoft through a major cultural and technological shift. His leadership is often associated with empathy, learning, and a growth mindset.", "Changing a large company is difficult because people become used to old habits. Nadella emphasized collaboration, cloud computing, and openness to learning. This helped Microsoft adapt to a new technology era.", "His story is helpful for teenagers because it shows that leadership is not only charisma. Culture, listening, humility, and willingness to learn can transform teams."],
    "questions": ["What is a growth mindset?", "How can empathy help a technology leader?", "Why is changing culture harder than changing a product?"],
    "activity": "Identify one fixed-mindset sentence and rewrite it as a growth-mindset sentence."
  },
  {
    "slug": "jensen-huang",
    "month": "Section 7",
    "category": "Innovators, Entrepreneurs & Changemakers",
    "type": "Person",
    "title": "Jensen Huang",
    "subtitle": "Semiconductors, persistence, AI infrastructure, and technical vision.",
    "readTime": "7 min",
    "skill": "Technical Vision",
    "vocabulary": ["semiconductor", "GPU", "infrastructure", "AI", "persistence"],
    "reading": ["Jensen Huang co-founded NVIDIA and helped build a company focused on graphics processing chips. Over time, those chips became important not only for graphics, but also for scientific computing and artificial intelligence.", "The story of NVIDIA shows how a technical idea can become more valuable as the world changes. Leaders must sometimes keep building before everyone else understands why the work matters.", "For teenagers, Huang's story connects engineering depth with business vision. It shows that modern innovation often depends on invisible infrastructure: chips, tools, systems, and teams that make breakthroughs possible."],
    "questions": ["Why can infrastructure be as important as a final product?", "How did GPUs become useful beyond graphics?", "What does technical vision require from a leader?"],
    "activity": "Explain one invisible technology that makes your daily life possible."
  },
  {
    "slug": "larry-page-sergey-brin",
    "month": "Section 7",
    "category": "Innovators, Entrepreneurs & Changemakers",
    "type": "People",
    "title": "Larry Page & Sergey Brin",
    "subtitle": "Search, algorithms, teamwork, and organizing information.",
    "readTime": "7 min",
    "skill": "Problem Framing",
    "vocabulary": ["algorithm", "search", "ranking", "teamwork", "information"],
    "reading": ["Larry Page and Sergey Brin worked on the problem of searching the growing internet. Their idea was not simply to collect pages, but to rank information in a way that helped users find what mattered.", "Their work became Google, showing how a research problem can become a company when the problem is important enough and the solution is useful enough.", "For teenagers, their story teaches problem framing. Great innovation often begins with asking a better question: how can we organize information so people can use it?"],
    "questions": ["Why was internet search such an important problem?", "How can a research idea become a company?", "What does it mean to frame a problem well?"],
    "activity": "Pick a messy information problem and design a better way to organize it."
  },
  {
    "slug": "dhirubhai-ambani",
    "month": "Section 7",
    "category": "Innovators, Entrepreneurs & Changemakers",
    "type": "Person",
    "title": "Dhirubhai Ambani",
    "subtitle": "Entrepreneurship, scale, ambition, and building from limited beginnings.",
    "readTime": "7 min",
    "skill": "Entrepreneurial Drive",
    "vocabulary": ["entrepreneur", "scale", "ambition", "market", "opportunity"],
    "reading": ["Dhirubhai Ambani is remembered as one of India's most influential entrepreneurs. His journey is often discussed as an example of ambition, market understanding, and building a large business from modest beginnings.", "Entrepreneurship requires noticing opportunities that others may ignore. It also requires courage, persistence, and the ability to bring people, capital, and systems together.", "For teenagers, Ambani's story opens discussion about ambition and responsibility. Building big companies can create jobs and change markets, but entrepreneurs must also think about ethics, trust, and long-term impact."],
    "questions": ["What does entrepreneurial drive mean?", "How can someone notice opportunity before others do?", "Why do ethics matter when a business grows large?"],
    "activity": "Identify one local need and imagine a small business that could serve it."
  },
  {
    "slug": "narayana-murthy",
    "month": "Section 7",
    "category": "Innovators, Entrepreneurs & Changemakers",
    "type": "Person",
    "title": "Narayana Murthy",
    "subtitle": "Infosys, professional excellence, values, and institution building.",
    "readTime": "7 min",
    "skill": "Values-Based Business",
    "vocabulary": ["institution", "integrity", "excellence", "team", "global"],
    "reading": ["Narayana Murthy co-founded Infosys and became known for helping build a global Indian technology services company. His story is often connected with professionalism, values, and institution building.", "A company becomes stronger when it is not only about one person. Systems, trust, transparency, and talented teams help an organization last beyond its founders.", "For teenagers, Murthy's example shows that business leadership can include discipline, fairness, and respect for process. Values are not separate from success; they can help create durable success."],
    "questions": ["What makes a company an institution?", "Why does transparency build trust?", "How can values and business success support each other?"],
    "activity": "Write three values you would want your future team or company to follow."
  },
  {
    "slug": "ratan-tata",
    "month": "Section 7",
    "category": "Innovators, Entrepreneurs & Changemakers",
    "type": "Person",
    "title": "Ratan Tata",
    "subtitle": "Business leadership, trust, design ambition, and social responsibility.",
    "readTime": "7 min",
    "skill": "Responsible Leadership",
    "vocabulary": ["trust", "responsibility", "brand", "innovation", "community"],
    "reading": ["Ratan Tata led the Tata Group through major growth and became widely respected for thoughtful business leadership. His work connected industry, brand trust, design ambition, and social responsibility.", "Business leaders make decisions that affect employees, customers, communities, and the environment. Responsible leadership asks not only whether something can be profitable, but whether it is honorable and useful.", "For teenagers, Tata's story encourages a broader view of success. Reputation is built over time through choices that show consistency, humility, courage, and concern for society."],
    "questions": ["Why is trust valuable in business?", "What makes leadership responsible?", "How should companies balance profit and community impact?"],
    "activity": "Choose a company you admire and identify one reason people trust it."
  },
  {
    "slug": "kiran-mazumdar-shaw",
    "month": "Section 7",
    "category": "Innovators, Entrepreneurs & Changemakers",
    "type": "Person",
    "title": "Kiran Mazumdar-Shaw",
    "subtitle": "Biotechnology, courage, women in leadership, and healthcare innovation.",
    "readTime": "7 min",
    "skill": "Barrier Breaking",
    "vocabulary": ["biotechnology", "healthcare", "barrier", "research", "enterprise"],
    "reading": ["Kiran Mazumdar-Shaw built a major biotechnology company in India and became an important example of women in science and entrepreneurship. Her journey shows how courage and expertise can open doors in difficult fields.", "Biotechnology connects science with real human needs, including medicine, diagnosis, and healthcare access. It requires patience because research, regulation, and trust all matter.", "For teenagers, her story shows that innovation is not limited to apps or gadgets. Science-based entrepreneurship can improve lives and create new possibilities for society."],
    "questions": ["Why is biotechnology important for society?", "What barriers might women entrepreneurs face?", "How can science become social impact?"],
    "activity": "Research one health problem and describe how science or technology might help."
  },
  {
    "slug": "nandan-nilekani",
    "month": "Section 7",
    "category": "Innovators, Entrepreneurs & Changemakers",
    "type": "Person",
    "title": "Nandan Nilekani",
    "subtitle": "Digital public infrastructure, identity systems, and scale for society.",
    "readTime": "7 min",
    "skill": "Systems Thinking",
    "vocabulary": ["infrastructure", "identity", "systems", "scale", "public good"],
    "reading": ["Nandan Nilekani co-founded Infosys and later became closely associated with large-scale digital public infrastructure in India. His work helps students think about technology not only as private business, but also as public systems.", "Systems thinking means understanding how many parts work together: people, rules, software, security, access, and trust. Large systems can help millions, but they must be designed carefully.", "For teenagers, Nilekani's story shows that leadership can happen at the intersection of technology, policy, and public service. Good systems can make opportunity easier to access."],
    "questions": ["What is digital public infrastructure?", "Why does trust matter in large systems?", "How can technology serve the public good?"],
    "activity": "Map a system you use, such as school lunch, library checkout, or online payments."
  },
  {
    "slug": "cv-raman",
    "month": "Section 7",
    "category": "Innovators, Entrepreneurs & Changemakers",
    "type": "Person",
    "title": "C.V. Raman",
    "subtitle": "Curiosity, physics, discovery, and India's scientific confidence.",
    "readTime": "7 min",
    "skill": "Scientific Curiosity",
    "vocabulary": ["physics", "discovery", "light", "experiment", "curiosity"],
    "reading": ["C.V. Raman was an Indian physicist whose work on the scattering of light became known as the Raman Effect. His discovery brought global recognition to Indian science.", "Scientific discovery begins with curiosity and careful observation. Raman's work reminds students that asking why can lead to experiments, evidence, and new understanding.", "For teenagers, Raman's story encourages respect for deep study. Innovation is not always a company; sometimes it is a discovery that changes how humans understand nature."],
    "questions": ["Why is curiosity important in science?", "How does observation lead to discovery?", "What is the difference between invention and discovery?"],
    "activity": "Observe a simple natural phenomenon and write three scientific questions about it."
  },
  {
    "slug": "homi-bhabha",
    "month": "Section 7",
    "category": "Innovators, Entrepreneurs & Changemakers",
    "type": "Person",
    "title": "Homi Bhabha",
    "subtitle": "Institution building, science leadership, and national development.",
    "readTime": "7 min",
    "skill": "Institution Building",
    "vocabulary": ["research", "institution", "physics", "strategy", "nation"],
    "reading": ["Homi Bhabha was a scientist and institution builder who played a major role in developing India's scientific research capacity. He understood that a nation needs strong institutions, not only individual talent.", "Science leadership requires long-term planning: laboratories, teachers, funding, training, and a culture of research. These foundations allow future generations to do important work.", "For teenagers, Bhabha's life shows that leadership can mean building the places where others will succeed. A great institution multiplies the talents of many people."],
    "questions": ["Why do scientific institutions matter?", "How can leaders support future generations?", "What is the difference between personal success and institution building?"],
    "activity": "Design a youth science center and list the first three resources it would need."
  },
  {
    "slug": "vikram-sarabhai",
    "month": "Section 7",
    "category": "Innovators, Entrepreneurs & Changemakers",
    "type": "Person",
    "title": "Vikram Sarabhai",
    "subtitle": "Space science, national service, and technology for development.",
    "readTime": "7 min",
    "skill": "Purposeful Innovation",
    "vocabulary": ["space", "development", "satellite", "purpose", "research"],
    "reading": ["Vikram Sarabhai is widely remembered as a key figure in India's space program. He believed advanced science and technology should help national development and improve people's lives.", "Space technology is not only about rockets. Satellites can support communication, weather, education, disaster response, and planning. Sarabhai's vision connected science with public purpose.", "For teenagers, his story shows that innovation is strongest when it serves a meaningful mission. A powerful idea becomes more inspiring when it helps people."],
    "questions": ["How can space technology help ordinary people?", "Why does innovation need purpose?", "What mission would make science meaningful to you?"],
    "activity": "Choose one satellite use and explain how it helps society."
  },
  {
    "slug": "katherine-johnson",
    "month": "Section 7",
    "category": "Innovators, Entrepreneurs & Changemakers",
    "type": "Person",
    "title": "Katherine Johnson",
    "subtitle": "Mathematics, precision, courage, and opening doors in STEM.",
    "readTime": "7 min",
    "skill": "Precision",
    "vocabulary": ["mathematics", "precision", "trajectory", "STEM", "perseverance"],
    "reading": ["Katherine Johnson was a mathematician whose calculations helped important American space missions. She worked in a time when both women and Black Americans faced serious barriers in many professional fields.", "Her work required precision because small mathematical errors can have huge consequences in spaceflight. She earned trust through excellence, persistence, and calm skill.", "For teenagers, Johnson's story teaches that intelligence must be joined with courage. She opened doors not by avoiding difficulty, but by doing excellent work in spite of it."],
    "questions": ["Why does precision matter in spaceflight?", "What barriers did Katherine Johnson face?", "How can excellence help challenge unfair assumptions?"],
    "activity": "Explain one field where small errors can have major consequences."
  },
  {
    "slug": "albert-einstein",
    "month": "Section 7",
    "category": "Innovators, Entrepreneurs & Changemakers",
    "type": "Person",
    "title": "Albert Einstein",
    "subtitle": "Imagination, physics, questioning assumptions, and moral responsibility.",
    "readTime": "7 min",
    "skill": "Imaginative Thinking",
    "vocabulary": ["relativity", "imagination", "theory", "assumption", "responsibility"],
    "reading": ["Albert Einstein changed physics by asking deep questions about light, time, space, and motion. His work shows that imagination and disciplined reasoning can transform human understanding.", "Einstein also reminds students that knowledge carries responsibility. Scientists and thinkers must consider how powerful ideas may affect society.", "For teenagers, Einstein's story encourages both wonder and humility. Great thinking often begins when someone is willing to question assumptions that everyone else accepts."],
    "questions": ["Why is imagination important in science?", "What does it mean to question assumptions?", "Why should scientists think about responsibility?"],
    "activity": "Take one everyday assumption and ask three 'what if' questions about it."
  },
  {
    "slug": "mahatma-gandhi",
    "month": "Section 7",
    "category": "Innovators, Entrepreneurs & Changemakers",
    "type": "Person",
    "title": "Mahatma Gandhi",
    "subtitle": "Truth, nonviolence, moral courage, and social change.",
    "readTime": "7 min",
    "skill": "Moral Courage",
    "vocabulary": ["truth", "nonviolence", "justice", "discipline", "change"],
    "reading": ["Mahatma Gandhi led through the principles of truth and nonviolence. His approach showed that social change can come from moral courage, discipline, and the ability to mobilize ordinary people.", "Gandhi's leadership was not based on wealth or weapons. It was based on conviction, sacrifice, communication, and the willingness to live by his values.", "For teenagers, Gandhi's life raises important questions about courage and ethics. How can a person resist injustice without hatred? How can personal discipline become public leadership?"],
    "questions": ["What makes nonviolence powerful?", "How did Gandhi connect personal discipline with leadership?", "How can students stand up for truth respectfully?"],
    "activity": "Identify one unfair situation and describe a peaceful, respectful response."
  },
  {
    "slug": "mother-teresa",
    "month": "Section 7",
    "category": "Innovators, Entrepreneurs & Changemakers",
    "type": "Person",
    "title": "Mother Teresa",
    "subtitle": "Compassion, service, dignity, and caring for the vulnerable.",
    "readTime": "7 min",
    "skill": "Compassionate Service",
    "vocabulary": ["compassion", "service", "dignity", "poverty", "care"],
    "reading": ["Mother Teresa became known for serving poor and vulnerable people, especially in Kolkata. Her work centered on seeing dignity in people whom society often ignored.", "Compassionate service asks leaders to notice suffering and respond with action. It may not look glamorous, but it can profoundly affect the person being helped and the person serving.", "For teenagers, her life encourages a practical question: who around us needs care, attention, or respect? Leadership can begin with noticing one person others overlook."],
    "questions": ["What does dignity mean?", "Why is service a form of leadership?", "How can small acts of care create impact?"],
    "activity": "Choose one practical act of service you can complete this week."
  },
  {
    "slug": "nelson-mandela",
    "month": "Section 7",
    "category": "Innovators, Entrepreneurs & Changemakers",
    "type": "Person",
    "title": "Nelson Mandela",
    "subtitle": "Justice, forgiveness, patience, and rebuilding a nation.",
    "readTime": "7 min",
    "skill": "Reconciliation",
    "vocabulary": ["justice", "forgiveness", "reconciliation", "patience", "freedom"],
    "reading": ["Nelson Mandela became a global symbol of resistance to apartheid and later of reconciliation. After many years in prison, he helped lead South Africa through a difficult transition.", "Mandela's leadership is powerful because he did not use victory as a chance for revenge. He emphasized justice, dignity, forgiveness, and building a shared future.", "For teenagers, Mandela's story teaches emotional strength. It is difficult to forgive without ignoring injustice, but great leaders sometimes help people move from pain toward rebuilding."],
    "questions": ["What is reconciliation?", "How can forgiveness and justice work together?", "Why does rebuilding require patience?"],
    "activity": "Think of a conflict and write one step that could help rebuild trust."
  }
]
'@

$modernItems = @()
$parsedModernItems = ConvertFrom-Json -InputObject $modernItemsJson
foreach ($modernItem in $parsedModernItems) {
  $modernItems += $modernItem
}
$items = @($items + $modernItems)

$explorerItemsJson = @'
[
  {
    "slug": "christopher-columbus",
    "month": "Topic 5",
    "category": "Explorers & Adventurers",
    "type": "Person",
    "title": "Christopher Columbus",
    "subtitle": "Navigation, ambition, risk, and the complicated consequences of exploration.",
    "readTime": "6 min",
    "skill": "Thinking About Consequences",
    "vocabulary": ["navigation", "voyage", "expedition", "consequence", "encounter", "trade", "colonization"],
    "reading": ["Christopher Columbus was an Italian sailor who sailed for Spain in 1492. He hoped to find a western sea route from Europe to Asia, where Europeans wanted access to spices, silk, and other valuable goods. Instead of reaching Asia, his ships crossed the Atlantic Ocean and reached islands in the Caribbean. This voyage became one of the most famous turning points in world history.", "Columbus showed courage, persistence, and confidence as a navigator. Crossing a vast ocean with limited maps and uncertain information required determination. His crew faced fear, fatigue, and confusion. A leader in such a situation had to keep people focused while making decisions with incomplete knowledge.", "At the same time, Columbus's story must be discussed honestly. His voyages opened the way for European colonization, which brought serious harm to Indigenous peoples through violence, forced labor, disease, and loss of land. A responsible student presenter should not treat exploration only as adventure. The deeper lesson is that bold actions can have consequences far beyond what the leader first imagines.", "For Yuva Club, Columbus is useful because he helps students practice balanced thinking. We can recognize courage and navigation skill while also asking ethical questions. What does discovery mean if people already live in the place being discovered? How should leaders think about the people affected by their goals? This page trains students to discuss history with curiosity, honesty, and respect."],
    "questions": ["Why did Columbus want to sail west across the Atlantic?", "What leadership qualities helped him continue when the voyage was uncertain?", "Why is it important to discuss both courage and harm in Columbus's story?", "What does the word discovery mean when people already live in a place?", "How can modern leaders think more carefully about the consequences of their plans?"],
    "activity": "Prepare a two-sided presentation: one slide on Columbus's navigation challenge and one slide on the consequences of his voyages."
  },
  {
    "slug": "vasco-da-gama",
    "month": "Topic 5",
    "category": "Explorers & Adventurers",
    "type": "Person",
    "title": "Vasco da Gama",
    "subtitle": "Sea routes, global trade, preparation, and cross-cultural encounters.",
    "readTime": "6 min",
    "skill": "Strategic Planning",
    "vocabulary": ["maritime", "route", "trade", "monsoon", "navigation", "encounter", "strategy"],
    "reading": ["Vasco da Gama was a Portuguese explorer who helped connect Europe and India by sea. In 1497, he sailed from Portugal around the southern tip of Africa, crossed the Indian Ocean, and reached Calicut on India's Malabar Coast in 1498. This voyage showed that European ships could reach India by sailing around Africa instead of relying only on land routes or middlemen.", "The journey required planning, endurance, and technical skill. Sailors had to understand winds, currents, supplies, ships, and timing. The Indian Ocean was not empty or unknown to the people who lived around it. It already had active trade networks linking East Africa, Arabia, India, and Southeast Asia. Da Gama entered a world of experienced merchants and sailors.", "His voyage changed global trade, but it also increased European competition and conflict in the Indian Ocean. Students should understand that exploration often mixed curiosity, trade, ambition, and power. A leader may open new connections, but those connections can become unfair or violent if respect and ethics are missing.", "For Yuva Club, Vasco da Gama's story is a chance to discuss strategy. He pursued a clear goal, followed a difficult route, and depended on navigation knowledge and teamwork. But the story also asks students to think about respect between cultures. Successful leadership is not only reaching the destination; it is also how you behave when you arrive."],
    "questions": ["What made the sea route to India important for Portugal?", "How did planning and navigation help da Gama's expedition?", "Why should we remember that the Indian Ocean already had strong trade networks?", "When does competition become harmful?", "How can a leader enter a new community respectfully?"],
    "activity": "Draw a simple map of da Gama's route and explain three challenges his crew had to plan for."
  },
  {
    "slug": "zheng-he",
    "month": "Topic 5",
    "category": "Explorers & Adventurers",
    "type": "Person",
    "title": "Zheng He",
    "subtitle": "Large-scale voyages, diplomacy, organization, and peaceful influence.",
    "readTime": "6 min",
    "skill": "Diplomatic Leadership",
    "vocabulary": ["admiral", "diplomacy", "fleet", "tribute", "logistics", "navigation", "ambassador"],
    "reading": ["Zheng He was a Chinese admiral and diplomat during the Ming dynasty. Between 1405 and 1433, he led seven major voyages across the Indian Ocean. His fleets traveled to Southeast Asia, India, Arabia, and the coast of East Africa. These voyages were among the largest maritime expeditions of their time.", "Zheng He's leadership required organization on a huge scale. A fleet needed ships, sailors, translators, supplies, maps, trade goods, and clear command. The purpose was not only travel. Zheng He represented the Ming court, built relationships, exchanged gifts, and showed China's power and wealth to other regions.", "Unlike some later European voyages, Zheng He's expeditions are often remembered more for diplomacy and display than permanent conquest. Still, they involved state power, military strength, and political goals. Students should ask how nations use exploration to build influence. A fleet can be a bridge for friendship, but it can also show power.", "For Yuva Club, Zheng He teaches students that leadership can mean coordinating many people toward a complex goal. He also shows the value of cultural understanding. An explorer who visits many places must learn to communicate, observe, and represent something larger than himself. His story connects adventure with diplomacy, logistics, and respect."],
    "questions": ["Why did Zheng He's voyages require strong organization?", "How is diplomacy different from conquest?", "What skills would an admiral need to lead a large fleet?", "How can travel build friendship between cultures?", "What responsibilities come with representing your country or community?"],
    "activity": "Design a mission plan for a peaceful expedition: include destination, purpose, team roles, supplies, and how you would show respect to hosts."
  },
  {
    "slug": "neil-armstrong",
    "month": "Topic 5",
    "category": "Explorers & Adventurers",
    "type": "Person",
    "title": "Neil Armstrong",
    "subtitle": "Moon exploration, calm under pressure, engineering, and teamwork.",
    "readTime": "6 min",
    "skill": "Calm Under Pressure",
    "vocabulary": ["astronaut", "mission", "module", "orbit", "engineering", "pressure", "precision"],
    "reading": ["Neil Armstrong was an American astronaut, test pilot, and engineer. In 1969, he commanded Apollo 11 and became the first person to walk on the Moon. The mission was not a single-person achievement. It depended on thousands of scientists, engineers, technicians, mission controllers, and astronauts working toward one extraordinary goal.", "Armstrong was known for calmness and technical focus. Before Apollo 11, he had flown as a test pilot and astronaut, where quick thinking could save lives. During the Moon landing, the lunar module had to be guided carefully while alarms sounded and fuel became limited. Armstrong and Buzz Aldrin landed safely while Michael Collins remained in orbit.", "Armstrong's first step on the Moon became a symbol of human possibility. But the leadership lesson is not only fame. Armstrong did not act like a celebrity hero. He often emphasized the mission, the team, and the engineering achievement. That humility is important for students: when a team succeeds, a good leader remembers the people who made success possible.", "For Yuva Club, Armstrong's story teaches preparation, courage, and focus. Exploration is not only rushing into danger. It is training, planning, practicing, and staying steady when the moment becomes difficult. Students can connect this to public speaking: prepare well, trust your training, and stay calm when everyone is watching."],
    "questions": ["Why was Apollo 11 a team achievement?", "How did calmness help Armstrong during the Moon landing?", "What is the difference between bravery and preparation?", "Why is humility important after a great achievement?", "How can students use Armstrong's example before giving a presentation?"],
    "activity": "Create a mission-control checklist for your next presentation: preparation, backup plan, opening sentence, key points, and calm-down strategy."
  },
  {
    "slug": "edmund-hillary",
    "month": "Topic 5",
    "category": "Explorers & Adventurers",
    "type": "Person",
    "title": "Edmund Hillary",
    "subtitle": "Everest, teamwork, humility, and service after achievement.",
    "readTime": "6 min",
    "skill": "Humble Achievement",
    "vocabulary": ["summit", "expedition", "mountaineering", "philanthropy", "humility", "endurance", "trust"],
    "reading": ["Edmund Hillary was a New Zealand mountaineer, explorer, and humanitarian. In 1953, he and Tenzing Norgay became the first climbers confirmed to reach the summit of Mount Everest. The climb was part of a large British expedition, which included many climbers, Sherpa guides, support workers, and planners.", "Climbing Everest required endurance, courage, trust, and teamwork. Hillary and Tenzing depended on each other in dangerous conditions where weather, altitude, ice, and exhaustion could threaten their lives. Their success reminds students that great achievements are rarely done alone. Even the people at the summit stand on the work of a team.", "Hillary's life after Everest is especially important. He did not only enjoy fame. He helped establish the Himalayan Trust and supported schools, hospitals, and other projects for Sherpa communities in Nepal. His story shows that achievement can become service. The question is not only, What did I accomplish? It is also, How can my success help others?", "For Yuva Club, Hillary is a strong example of humility after success. He reached one of the world's most famous goals, but he is also remembered for generosity and practical service. Students can learn that leadership continues after the applause ends."],
    "questions": ["Why was the Everest climb a team achievement?", "What kind of trust did Hillary and Tenzing need?", "Why is service after success an important leadership lesson?", "How can fame be used in a positive way?", "What does humility look like when someone has achieved something great?"],
    "activity": "Choose one achievement you hope to reach and write one way that success could help other people."
  },
  {
    "slug": "tenzing-norgay",
    "month": "Topic 5",
    "category": "Explorers & Adventurers",
    "type": "Person",
    "title": "Tenzing Norgay",
    "subtitle": "Everest, courage, partnership, Sherpa expertise, and shared success.",
    "readTime": "6 min",
    "skill": "Partnership",
    "vocabulary": ["Sherpa", "summit", "expedition", "rope team", "altitude", "partnership", "recognition"],
    "reading": ["Tenzing Norgay was a Sherpa mountaineer who became one of the most famous climbers in history. On May 29, 1953, he and Edmund Hillary reached the summit of Mount Everest together. Tenzing had deep mountain experience and had taken part in earlier Everest attempts before the successful 1953 expedition.", "Tenzing's story helps students understand that expertise can come from lived experience, not only from formal titles. Sherpa climbers were essential to Himalayan expeditions because they knew the mountains, carried heavy loads, fixed routes, supported climbers, and made difficult decisions in dangerous conditions. Without their skill and courage, many famous climbs would not have happened.", "The summit with Hillary became a symbol of partnership. They were tied together by rope and depended on each other for safety. Their achievement also raises questions about recognition. History sometimes celebrates one person more than the team or community behind the achievement. A fair leader gives credit to everyone who contributes.", "For Yuva Club, Tenzing Norgay teaches courage, teamwork, and dignity. He reminds students to notice the people whose work makes success possible. In school projects, sports, clubs, and families, some people may stand in front while others support from behind. True leadership respects both."],
    "questions": ["Why was Sherpa expertise essential for Everest expeditions?", "What does Tenzing's partnership with Hillary teach about trust?", "Why is recognition important after a team achievement?", "How can students make sure quieter contributors receive credit?", "What does courage look like when you are helping someone else succeed?"],
    "activity": "Think of a team success and list every person who helped. Then explain how you would publicly thank them."
  }
]
'@

$explorerItems = ConvertFrom-Json -InputObject $explorerItemsJson
$items = @($items + $explorerItems)

$civilizationItemsJson = @'
[
  {
    "slug": "indus-valley-civilization",
    "month": "Topic 6",
    "category": "Ancient Civilizations",
    "type": "Civilization",
    "title": "Indus Valley Civilization",
    "subtitle": "Urban planning, trade, sanitation, and organized community life.",
    "readTime": "6 min",
    "skill": "Systems Thinking",
    "vocabulary": ["civilization", "urban planning", "drainage", "trade", "standardization", "archaeology", "community"],
    "reading": ["The Indus Valley Civilization grew along the Indus River region and nearby areas of South Asia. Its major cities included Harappa and Mohenjo-daro. Archaeologists have found evidence of planned streets, brick buildings, wells, drainage systems, weights, seals, craft production, and trade. These details show that the people of the Indus Valley built organized cities thousands of years ago.", "One of the most impressive features of this civilization was urban planning. Streets were laid out carefully, houses often had access to water, and drainage systems helped keep cities cleaner. This tells us that leadership is not only about speeches or battles. Sometimes leadership appears in systems that help many people live safely and efficiently.", "The Indus script has not been fully understood, so many questions remain. We do not know everything about their government, beliefs, or language. This uncertainty is an important lesson for students: history is built from evidence, and good researchers must be honest about what is known and what is still debated.", "For Yuva Club, the Indus Valley Civilization teaches students to notice quiet achievements. A well-planned city may not sound as dramatic as a battle, but clean water, reliable trade, skilled crafts, and shared standards can shape daily life. A presenter should ask: what kind of teamwork does a city need to function well?"],
    "questions": ["What does urban planning tell us about the people of the Indus Valley?", "Why are water and drainage systems important for community life?", "How should historians speak when some evidence is still uncertain?", "What kind of leadership is shown through city planning?", "What can modern communities learn from ancient city systems?"],
    "activity": "Design a simple model city with roads, homes, water, public spaces, and waste systems. Explain how each part helps people live together."
  },
  {
    "slug": "ancient-egypt",
    "month": "Topic 6",
    "category": "Ancient Civilizations",
    "type": "Civilization",
    "title": "Ancient Egypt",
    "subtitle": "The Nile, pyramids, writing, engineering, and long-lasting cultural memory.",
    "readTime": "6 min",
    "skill": "Long-Term Vision",
    "vocabulary": ["Nile", "pharaoh", "hieroglyphics", "pyramid", "irrigation", "dynasty", "afterlife"],
    "reading": ["Ancient Egypt developed along the Nile River, one of the most important rivers in world history. The Nile provided water, fertile soil, transportation, and a rhythm for farming. Because the river flooded regularly, Egyptian society learned to organize agriculture, store food, and plan for seasons.", "Egypt is famous for pyramids, temples, tombs, art, and hieroglyphic writing. These achievements required engineers, artists, scribes, workers, priests, rulers, and planners. A pyramid was not only a giant structure; it was the result of organization, mathematics, labor, belief, and leadership across many years.", "Ancient Egyptian civilization lasted for a very long time. That long history teaches students that civilizations are not built in a day. They require habits, institutions, shared stories, and ways to pass knowledge from one generation to the next. Writing was especially important because it helped people record laws, rituals, trade, history, and ideas.", "For Yuva Club, Egypt raises a powerful leadership question: how do people build something that lasts beyond their own lifetime? Some Egyptian achievements were connected to strong rulers and social hierarchy, but they also show planning, skill, and cultural identity. Students can discuss both the beauty of achievement and the human cost of large projects."],
    "questions": ["Why was the Nile River so important to ancient Egypt?", "What skills were needed to build pyramids and temples?", "How did writing help Egyptian civilization last?", "What are the benefits and risks of very large public projects?", "What does it mean to build something for future generations?"],
    "activity": "Choose one Egyptian achievement and explain the teamwork, planning, and values behind it."
  },
  {
    "slug": "ancient-greece",
    "month": "Topic 6",
    "category": "Ancient Civilizations",
    "type": "Civilization",
    "title": "Ancient Greece",
    "subtitle": "Democracy, philosophy, city-states, art, athletics, and debate.",
    "readTime": "6 min",
    "skill": "Questioning and Debate",
    "vocabulary": ["city-state", "democracy", "philosophy", "citizen", "debate", "mythology", "Olympics"],
    "reading": ["Ancient Greece was not one single united country in the modern sense. It was made of city-states such as Athens, Sparta, Corinth, and Thebes. These city-states had different governments, values, and ways of life. Athens became famous for democracy and public debate, while Sparta became known for discipline and military training.", "Greek thinkers asked big questions about justice, courage, knowledge, friendship, government, and the purpose of life. Philosophers such as Socrates, Plato, and Aristotle influenced later civilizations. Greek drama, art, architecture, mythology, and the Olympic Games also shaped world culture.", "The Greek example is useful because it shows the power of questions. A society grows when people ask: What is fair? What is a good life? How should citizens participate? At the same time, Greek democracy was limited. Women, enslaved people, and many residents did not have equal political rights. Students should learn from both the achievements and the limits.", "For Yuva Club, Ancient Greece is a natural topic for public speaking. It teaches students to compare ideas, defend opinions, listen to opposing views, and ask better questions. Leadership is not only giving orders; it can also mean helping a group think more clearly."],
    "questions": ["How were Greek city-states different from one another?", "Why is debate important in a community?", "What can we admire about ancient Greek democracy, and what were its limits?", "Why do philosophical questions still matter today?", "How can students disagree respectfully during discussion?"],
    "activity": "Hold a mini debate on one question: What quality is most important for a good citizen?"
  },
  {
    "slug": "ancient-rome",
    "month": "Topic 6",
    "category": "Ancient Civilizations",
    "type": "Civilization",
    "title": "Ancient Rome",
    "subtitle": "Republic, empire, law, roads, engineering, power, and responsibility.",
    "readTime": "6 min",
    "skill": "Responsible Power",
    "vocabulary": ["republic", "empire", "senate", "law", "aqueduct", "citizenship", "engineering"],
    "reading": ["Ancient Rome began as a city and grew into a republic, then a vast empire. Roman influence spread across much of Europe, North Africa, and the Middle East. Rome is remembered for law, roads, aqueducts, military organization, architecture, literature, and government ideas that influenced later societies.", "The Roman Republic included institutions such as the Senate and elected officials, though political rights were not equal for everyone. Over time, power struggles, inequality, military ambition, and civil wars weakened the republic. Rome's story helps students ask why governments need both strong leadership and limits on power.", "Roman engineering was practical and impressive. Roads connected distant places, aqueducts carried water, and public buildings supported urban life. These projects made the empire easier to manage, but they also served Roman power. A road can connect people and trade; it can also move armies.", "For Yuva Club, Ancient Rome teaches that leadership with power must include responsibility. Building systems, laws, and infrastructure can help society, but ambition without ethics can create conflict. Students should discuss how institutions protect communities from selfish leadership."],
    "questions": ["What made Roman roads and aqueducts important?", "Why do governments need rules that limit power?", "How did Rome combine practical engineering with political control?", "What can modern leaders learn from the fall of the Roman Republic?", "When does ambition become dangerous?"],
    "activity": "Create a chart with two columns: Roman achievements and leadership warnings. Present one example from each column."
  },
  {
    "slug": "mesopotamia",
    "month": "Topic 6",
    "category": "Ancient Civilizations",
    "type": "Civilization",
    "title": "Mesopotamia",
    "subtitle": "Cities, writing, laws, irrigation, and civilization between rivers.",
    "readTime": "6 min",
    "skill": "Organizing Society",
    "vocabulary": ["Mesopotamia", "Tigris", "Euphrates", "cuneiform", "irrigation", "city-state", "law code"],
    "reading": ["Mesopotamia means land between rivers. It developed between the Tigris and Euphrates rivers in the region of modern Iraq and nearby areas. Civilizations such as Sumer, Akkad, Babylon, and Assyria grew there. Mesopotamia is often studied as one of the early centers of cities, writing, law, agriculture, and organized government.", "Irrigation helped farmers grow crops in a dry region, but irrigation required cooperation. Canals had to be planned, built, cleaned, and protected. This shows how geography can shape leadership. When people depend on shared water systems, they need rules, labor, and trust.", "Mesopotamians developed cuneiform writing, which was used for records, trade, stories, and laws. The Code of Hammurabi is one famous example of written law from Babylon. Written rules can help a society become more organized, though they can also reveal inequality in how different groups are treated.", "For Yuva Club, Mesopotamia teaches that civilization depends on systems: farming, writing, law, trade, and government. A presenter should explain how one invention, such as writing, can change many parts of life. Leadership often means creating order so people can work, trade, solve conflicts, and plan for the future."],
    "questions": ["Why were the Tigris and Euphrates rivers important?", "How did irrigation require cooperation?", "Why was writing such a powerful invention?", "What are the strengths and limits of written laws?", "What systems does a modern community need to function well?"],
    "activity": "Choose one system from Mesopotamia: irrigation, writing, law, trade, or cities. Explain how it changed daily life."
  },
  {
    "slug": "maya-civilization",
    "month": "Topic 6",
    "category": "Ancient Civilizations",
    "type": "Civilization",
    "title": "Maya Civilization",
    "subtitle": "Cities, calendars, astronomy, writing, mathematics, and rainforest adaptation.",
    "readTime": "6 min",
    "skill": "Observation and Knowledge",
    "vocabulary": ["Maya", "astronomy", "calendar", "glyph", "city-state", "mathematics", "rainforest"],
    "reading": ["The Maya civilization developed in Mesoamerica, including parts of present-day Mexico, Guatemala, Belize, Honduras, and El Salvador. Maya city-states built temples, plazas, palaces, and monuments. They created a writing system, studied astronomy, used calendars, and developed advanced mathematics.", "Maya knowledge came from careful observation. Watching the sky helped people track time, plan rituals, and understand cycles. Their calendars show that science, religion, agriculture, and leadership were connected in their society. Leaders used knowledge of time and ceremony to guide public life.", "Maya cities also show adaptation. Building and farming in rainforest environments required local knowledge, planning, and management of resources. Like many civilizations, Maya society had achievements and challenges, including competition among city-states, social hierarchy, and environmental pressures.", "For Yuva Club, the Maya civilization teaches students to respect knowledge systems that may look different from modern classrooms. Observation, mathematics, writing, and architecture all show intellectual strength. A good presenter should avoid stereotypes and explain what the Maya built, studied, and passed down."],
    "questions": ["What kinds of knowledge did the Maya develop?", "Why were calendars and astronomy important?", "How did environment shape Maya cities?", "Why should students avoid stereotypes when presenting ancient civilizations?", "How can observation become a leadership skill?"],
    "activity": "Observe one natural cycle, such as sunrise, moon phases, weather, or plant growth. Explain how careful observation can help a community."
  },
  {
    "slug": "inca-civilization",
    "month": "Topic 6",
    "category": "Ancient Civilizations",
    "type": "Civilization",
    "title": "Inca Civilization",
    "subtitle": "Andes mountains, roads, terraces, organization, and shared labor.",
    "readTime": "6 min",
    "skill": "Coordination",
    "vocabulary": ["Inca", "Andes", "terrace farming", "road network", "quipu", "empire", "coordination"],
    "reading": ["The Inca civilization grew in the Andes Mountains of South America and became the largest empire in the Americas before European conquest. The Inca built roads, bridges, storehouses, terraces, and cities across difficult mountain landscapes. Machu Picchu is one of the most famous Inca sites.", "The Andes are challenging. High mountains, steep slopes, and different climates made travel and farming difficult. The Inca responded with terrace farming, road systems, and careful organization. Their road network helped move messengers, goods, officials, and armies across the empire.", "The Inca used quipu, a system of knotted cords, to help record information. Their society relied heavily on organization and shared labor. This shows that leadership is not only personal bravery. It can also be the ability to coordinate thousands of people across distance, geography, and different communities.", "For Yuva Club, the Inca civilization teaches adaptation and planning. Instead of seeing mountains only as obstacles, the Inca built systems that worked with the land. Students can discuss how communities solve problems by understanding their environment and organizing people toward shared goals."],
    "questions": ["How did the Andes Mountains shape Inca life?", "Why were roads so important to the Inca Empire?", "What does terrace farming teach about adaptation?", "How can coordination be a leadership skill?", "What modern systems help people connect across long distances?"],
    "activity": "Design a plan to connect three mountain villages. Include roads, communication, food storage, and emergency support."
  },
  {
    "slug": "chinese-civilization",
    "month": "Topic 6",
    "category": "Ancient Civilizations",
    "type": "Civilization",
    "title": "Chinese Civilization",
    "subtitle": "Dynasties, philosophy, inventions, writing, government, and continuity.",
    "readTime": "6 min",
    "skill": "Learning Across Generations",
    "vocabulary": ["dynasty", "ancestor", "Confucianism", "Daoism", "bureaucracy", "invention", "continuity"],
    "reading": ["Ancient Chinese civilization developed along rivers such as the Yellow River and Yangtze River. Over time, dynasties rose and fell, but many cultural patterns continued: respect for learning, family, writing, government service, agriculture, philosophy, and invention. Chinese civilization is one of the world's longest continuous cultural traditions.", "Ancient China contributed ideas and technologies that influenced the world, including paper, printing, the compass, and gunpowder. Chinese writing helped connect people across regions and generations. Government systems also developed ways to manage large populations and territories.", "Chinese philosophy asked practical questions about life and leadership. Confucian ideas emphasized ethics, family responsibility, education, and good government. Daoist ideas encouraged harmony with nature and simplicity. These traditions show that civilizations are shaped not only by buildings and armies, but also by ideas about how people should live.", "For Yuva Club, Chinese civilization teaches continuity. A society becomes strong when it passes knowledge, values, and skills from one generation to the next while still adapting to change. Students can ask: what traditions should we preserve, and what should we improve?"],
    "questions": ["Why are rivers important in the growth of civilizations?", "How did writing help Chinese civilization continue across generations?", "What leadership ideas can students learn from Confucianism or Daoism?", "How can inventions change the world beyond their original culture?", "What traditions in your own family or community are worth passing on?"],
    "activity": "Choose one Chinese invention or idea and explain how it influenced later generations."
  }
]
'@

$civilizationItems = ConvertFrom-Json -InputObject $civilizationItemsJson

$growthItemsJson = @'
[
  {
    "slug": "national-parks",
    "month": "Topic 11",
    "category": "Environment & Nature",
    "type": "Nature",
    "title": "National Parks",
    "subtitle": "Protected places, public lands, conservation, and shared responsibility.",
    "readTime": "6 min",
    "skill": "Stewardship",
    "vocabulary": ["national park", "conservation", "habitat", "public land", "stewardship", "visitor impact", "biodiversity"],
    "reading": ["National parks protect special landscapes, historic places, plants, animals, and cultural stories for the public. They may include mountains, forests, deserts, rivers, caves, battlefields, monuments, or coastlines. A national park is not only a vacation spot. It is a promise that some places are valuable enough to protect for future generations.", "Parks need leadership because many people use the same space in different ways. Visitors want recreation, scientists want to study ecosystems, nearby communities may depend on tourism, and wildlife needs safe habitat. Park managers must balance access with protection. That means rules, education, trail care, safety plans, and respect for local and Indigenous histories.", "Students can learn stewardship from national parks. Stewardship means caring for something that belongs not only to us, but also to others and to the future. A young person can practice stewardship by staying on trails, reducing waste, respecting wildlife, learning the history of a place, and teaching others how to enjoy nature responsibly.", "For Yuva Club, this topic is a chance to discuss shared ownership. If a park belongs to everyone, everyone also has responsibility. A presenter can choose one park, explain what makes it special, describe one threat it faces, and suggest one action visitors can take to protect it."],
    "questions": ["Why should some places be protected for future generations?", "How should leaders balance visitor access with nature protection?", "What responsibilities do visitors have when they enter a park?", "How can parks teach both science and history?", "What is one natural place near you that deserves better care?"],
    "activity": "Choose one national park or local park and make a three-point visitor responsibility guide."
  },
  {
    "slug": "rivers",
    "month": "Topic 11",
    "category": "Environment & Nature",
    "type": "Nature",
    "title": "Rivers",
    "subtitle": "Water, communities, ecosystems, agriculture, and cooperation.",
    "readTime": "6 min",
    "skill": "Shared Responsibility",
    "vocabulary": ["river basin", "watershed", "irrigation", "pollution", "ecosystem", "floodplain", "cooperation"],
    "reading": ["Rivers have shaped human life for thousands of years. They provide water for drinking, farming, travel, energy, wildlife, and culture. Many great civilizations grew near rivers because water made settled life possible. Even today, rivers connect cities, farms, forests, and oceans.", "A river is part of a watershed, which means land where rain and streams drain toward the same river system. What happens upstream can affect people downstream. If trash, chemicals, or soil enter the water in one place, communities far away may feel the impact. This makes rivers a leadership topic, not just a science topic.", "Good river leadership requires cooperation. Farmers, city planners, families, businesses, and governments all make choices that affect water. Students can discuss questions of fairness: Who gets to use water? How do we prevent pollution? How should communities prepare for floods or droughts? These questions do not have easy one-line answers, which makes them useful for discussion.", "For Yuva Club, rivers teach interdependence. A presenter can choose a river such as the Ganga, Mississippi, Nile, Amazon, or a local river and explain how it supports life. The strongest presentations will connect geography, ecology, culture, and responsibility."],
    "questions": ["Why have rivers been important to civilizations and modern communities?", "How can an upstream choice affect people downstream?", "Who should be responsible for keeping rivers clean?", "How should communities share water during droughts?", "What can students do to protect local waterways?"],
    "activity": "Draw a simple watershed map showing how rain, streets, farms, homes, and streams connect to a river."
  },
  {
    "slug": "himalayas",
    "month": "Topic 11",
    "category": "Environment & Nature",
    "type": "Nature",
    "title": "Himalayas",
    "subtitle": "Mountains, rivers, climate, culture, courage, and fragile ecosystems.",
    "readTime": "6 min",
    "skill": "Respect for Limits",
    "vocabulary": ["Himalayas", "glacier", "monsoon", "altitude", "ecosystem", "watershed", "resilience"],
    "reading": ["The Himalayas are the world's highest mountain range and include Mount Everest. They stretch across several countries and influence weather, rivers, cultures, travel, and spiritual imagination. Many major rivers begin in or near Himalayan glaciers and snowfields, making the region important far beyond the mountains themselves.", "Mountains are beautiful, but they are also demanding. High altitude, cold temperatures, landslides, avalanches, and fragile ecosystems remind us that nature has limits. People who live in mountain regions often develop resilience, local knowledge, and careful habits because the environment requires attention and respect.", "The Himalayas are also connected to climate and water. Glaciers, snowmelt, monsoon patterns, and river systems affect millions of people. This makes the Himalayas a powerful topic for discussing how local environments can have regional and even global importance.", "For Yuva Club, this topic can connect science, culture, geography, and leadership. Students can discuss what courage means in mountains: not only climbing higher, but knowing when to prepare, listen to guides, protect ecosystems, and respect danger. Good leadership includes ambition and humility."],
    "questions": ["Why are the Himalayas important beyond the people who live there?", "What can mountains teach us about preparation and humility?", "How are glaciers and rivers connected?", "When is respecting a limit a form of courage?", "How can tourism help and harm fragile mountain areas?"],
    "activity": "Prepare a short mountain safety and respect checklist for hikers or tourists."
  },
  {
    "slug": "oceans",
    "month": "Topic 11",
    "category": "Environment & Nature",
    "type": "Nature",
    "title": "Oceans",
    "subtitle": "Ocean literacy, climate, life, trade, food, and responsible choices.",
    "readTime": "6 min",
    "skill": "Systems Thinking",
    "vocabulary": ["ocean literacy", "marine", "current", "climate", "ecosystem", "coast", "sustainability"],
    "reading": ["Oceans cover most of Earth and influence weather, climate, food, trade, travel, and culture. They are home to many forms of life, from tiny plankton to whales. Oceans may look far away from daily life, but even people living inland are connected to them through climate, water cycles, food systems, and global trade.", "Ocean literacy means understanding the ocean's influence on us and our influence on the ocean. This idea is useful for students because it turns knowledge into responsibility. If we use plastic, eat seafood, travel, buy products shipped across the world, or live near a coast, our choices connect to ocean health.", "Oceans also show systems thinking. A change in temperature, pollution, fishing, coral reefs, or currents can affect many other parts of the system. Leaders need systems thinking because real problems rarely stay in one box. A solution must consider science, economics, communities, and fairness.", "For Yuva Club, an oceans presentation can focus on one issue: coral reefs, plastic pollution, marine animals, shipping, coastal storms, or ocean exploration. The goal is not to scare listeners, but to help them understand connections and choose responsible action."],
    "questions": ["How do oceans affect people who do not live near the coast?", "What does it mean that the ocean influences us and we influence the ocean?", "Why do ocean problems require systems thinking?", "How can students reduce harm to oceans in daily life?", "Should ocean resources be treated as shared global responsibility?"],
    "activity": "Track one day of plastic use and suggest two realistic ways to reduce waste."
  },
  {
    "slug": "wildlife",
    "month": "Topic 11",
    "category": "Environment & Nature",
    "type": "Nature",
    "title": "Wildlife",
    "subtitle": "Animals, habitats, food webs, coexistence, and conservation choices.",
    "readTime": "6 min",
    "skill": "Empathy and Balance",
    "vocabulary": ["wildlife", "habitat", "food web", "species", "endangered", "coexistence", "conservation"],
    "reading": ["Wildlife includes animals that live in natural habitats: forests, grasslands, oceans, wetlands, deserts, mountains, and even cities. Each species has a role in its ecosystem. Bees pollinate, predators help balance populations, birds spread seeds, and many small creatures support soil and water health.", "Wildlife conservation is not only about loving animals. It is about protecting habitats, understanding food webs, and making wise choices when human needs and animal needs overlap. Roads, farms, cities, pollution, and climate can change habitats. Sometimes people and wildlife come into conflict, especially when space and resources are limited.", "Leadership in wildlife issues requires empathy and balance. Students can ask: How do we protect animals while respecting farmers, workers, and communities? How do we make decisions when one solution helps one group but creates problems for another? These questions teach critical thinking.", "For Yuva Club, a presenter can choose one species and explain its habitat, role, threats, and possible solutions. The best presentation will avoid simple blame and instead show how thoughtful cooperation can protect both people and nature."],
    "questions": ["Why is habitat protection as important as protecting individual animals?", "How can people and wildlife coexist when they need the same space?", "What should leaders consider before creating a conservation rule?", "Why are small species important in ecosystems?", "How can students speak about wildlife without oversimplifying the problem?"],
    "activity": "Choose one animal and create a four-part profile: habitat, role, threat, and solution."
  },
  {
    "slug": "climate",
    "month": "Topic 11",
    "category": "Environment & Nature",
    "type": "Nature",
    "title": "Climate",
    "subtitle": "Weather patterns, evidence, choices, adaptation, and responsible communication.",
    "readTime": "6 min",
    "skill": "Evidence-Based Thinking",
    "vocabulary": ["climate", "weather", "evidence", "greenhouse gas", "adaptation", "mitigation", "resilience"],
    "reading": ["Weather is what we experience day to day, such as rain, heat, snow, or wind. Climate is the pattern of weather over longer periods of time. Understanding the difference helps students speak clearly. One hot day or one cold day is weather; long-term patterns help us understand climate.", "Climate learning depends on evidence. Scientists study temperature records, ice, oceans, satellites, storms, plants, and many other signs. Students do not need to become climate scientists in one session, but they can learn how to ask good questions: What evidence supports this claim? Is the source trustworthy? What does the data show over time?", "Climate also requires leadership because it affects communities differently. Some places face stronger storms, heat, drought, flooding, or sea level rise. Leaders must think about both mitigation, which means reducing causes of future warming, and adaptation, which means preparing for changes already happening.", "For Yuva Club, climate is a chance to practice calm, respectful, evidence-based discussion. The goal is not to argue loudly. The goal is to understand systems, listen to science, and think about practical choices families, schools, cities, and countries can make."],
    "questions": ["What is the difference between weather and climate?", "How can students decide whether a climate source is trustworthy?", "Why do climate issues require both science and leadership?", "What is one example of mitigation and one example of adaptation?", "How can we discuss serious topics without becoming hopeless or disrespectful?"],
    "activity": "Compare weather and climate by tracking local weather for one week and explaining why one week is not the same as climate."
  },
  {
    "slug": "conservation",
    "month": "Topic 11",
    "category": "Environment & Nature",
    "type": "Nature",
    "title": "Conservation",
    "subtitle": "Protecting resources, reducing waste, restoring habitats, and acting wisely.",
    "readTime": "6 min",
    "skill": "Responsible Action",
    "vocabulary": ["conservation", "sustainability", "resource", "restore", "reduce", "reuse", "responsibility"],
    "reading": ["Conservation means protecting and wisely using natural resources such as water, forests, soil, energy, wildlife, and clean air. Conservation is not only about saying no to everything. It is about making choices that allow people and nature to continue thriving over time.", "Students often hear big environmental problems and feel the issues are too large. Conservation teaches that small actions matter when they become habits and when communities work together. Reducing waste, saving energy, planting native species, avoiding litter, and respecting habitats are examples of practical conservation.", "Conservation also requires judgment. Sometimes people disagree about land use, jobs, development, farming, recreation, or wildlife protection. A good leader listens to different needs and looks for solutions that are responsible, fair, and based on evidence.", "For Yuva Club, conservation is a practical leadership topic. A presenter can choose one resource, explain why it matters, identify one problem, and propose one action students can realistically take at home, school, or in the community."],
    "questions": ["What is the difference between using resources and wasting resources?", "Why do conservation choices sometimes create disagreement?", "How can small habits become community impact?", "What is one resource your family or school could conserve better?", "How can young people lead conservation without blaming others?"],
    "activity": "Create a one-week conservation challenge for your home or classroom."
  },
  {
    "slug": "doctors",
    "month": "Topic 12",
    "category": "Community & Service",
    "type": "Service",
    "title": "Doctors",
    "subtitle": "Healing, responsibility, teamwork, communication, and trust.",
    "readTime": "6 min",
    "skill": "Care and Responsibility",
    "vocabulary": ["doctor", "diagnosis", "patient", "empathy", "teamwork", "ethics", "prevention"],
    "reading": ["Doctors serve communities by helping people prevent illness, understand health problems, and receive care. Their work is not only science. It also requires listening, communication, patience, ethics, and teamwork with nurses, technicians, pharmacists, therapists, and families.", "A doctor often meets people when they are worried or in pain. That means trust matters. Patients need clear explanations and respect. Good doctors ask questions, study evidence, make careful decisions, and admit when more information is needed. This shows students that leadership includes humility.", "Doctors also teach prevention. Vaccines, nutrition, exercise, sleep, mental health, hygiene, and regular checkups can help communities stay healthier. A doctor who explains prevention well can help many people avoid future problems.", "For Yuva Club, doctors are an example of service leadership. A presenter can discuss a medical specialty, a public health challenge, or a doctor who inspired them. The key lesson is that knowledge becomes leadership when it is used to reduce suffering and protect life."],
    "questions": ["Why does a doctor need communication skills as well as science knowledge?", "How does trust affect healthcare?", "What is the difference between treating illness and preventing illness?", "How can doctors show humility while still leading?", "What can students learn from healthcare teamwork?"],
    "activity": "Interview a healthcare worker or research one medical role and present three skills it requires."
  },
  {
    "slug": "teachers",
    "month": "Topic 12",
    "category": "Community & Service",
    "type": "Service",
    "title": "Teachers",
    "subtitle": "Guidance, patience, preparation, encouragement, and lifelong learning.",
    "readTime": "6 min",
    "skill": "Mentorship",
    "vocabulary": ["teacher", "mentor", "lesson", "feedback", "encouragement", "patience", "growth"],
    "reading": ["Teachers help students build knowledge, habits, confidence, and curiosity. A teacher's work begins before class through planning, reading, organizing lessons, and thinking about what students need. During class, teachers explain, ask questions, listen, correct, encourage, and guide discussion.", "Great teachers do more than deliver information. They notice when a student is confused, shy, distracted, or ready for a challenge. They create a space where students can make mistakes and keep learning. This is why patience and encouragement are leadership qualities.", "Teachers also model lifelong learning. Good educators keep improving their own methods, learning new tools, and reflecting on what works. Students can learn from this: leadership does not mean already knowing everything. It means staying teachable.", "For Yuva Club, teachers connect directly to the Kids Teaching Kids philosophy. When students present, they become temporary teachers. They must prepare clearly, respect their listeners, explain ideas simply, and invite questions. Teaching is one of the best ways to learn."],
    "questions": ["What makes a teacher effective beyond knowing the subject?", "Why is patience important in leadership?", "How does teaching help the teacher learn more deeply?", "What kind of feedback helps students improve without feeling discouraged?", "How can Yuva Club presenters act like good teachers?"],
    "activity": "Teach the group one small concept in one minute, then ask one check-for-understanding question."
  },
  {
    "slug": "firefighters",
    "month": "Topic 12",
    "category": "Community & Service",
    "type": "Service",
    "title": "Firefighters",
    "subtitle": "Courage, preparation, teamwork, emergency response, and public safety.",
    "readTime": "6 min",
    "skill": "Prepared Courage",
    "vocabulary": ["firefighter", "emergency", "rescue", "prevention", "teamwork", "training", "safety"],
    "reading": ["Firefighters are often associated with bravery, but their courage is not random. It is prepared through training, equipment, teamwork, fitness, communication, and practice. They respond to fires, accidents, medical emergencies, rescues, and community safety needs.", "Firefighting shows that leadership in emergencies depends on preparation before the emergency happens. Teams practice roles, learn procedures, check equipment, and communicate clearly. When pressure is high, preparation helps people act with discipline instead of panic.", "Firefighters also teach prevention. Smoke alarms, escape plans, safe cooking, careful use of electricity, and fire drills can prevent tragedy. Public education is part of service because the best rescue is the one that never becomes necessary.", "For Yuva Club, firefighters teach that courage and caution belong together. A presenter can explain one emergency role, one safety habit, and one example of teamwork. Students should discuss how they can prepare for emergencies at home and school."],
    "questions": ["Why is firefighter courage different from taking careless risks?", "How does training help people stay calm in emergencies?", "Why is prevention part of public service?", "What emergency plan should every family discuss?", "How can students show leadership during drills or urgent situations?"],
    "activity": "Create a simple home emergency checklist with meeting place, contacts, and safety supplies."
  },
  {
    "slug": "police",
    "month": "Topic 12",
    "category": "Community & Service",
    "type": "Service",
    "title": "Police",
    "subtitle": "Public safety, responsibility, fairness, communication, and community trust.",
    "readTime": "6 min",
    "skill": "Fairness and Trust",
    "vocabulary": ["police", "public safety", "law", "fairness", "trust", "de-escalation", "responsibility"],
    "reading": ["Police officers are public servants whose work is connected to safety, law, emergency response, investigation, traffic, and community protection. Their role carries authority, which means it also carries serious responsibility. Authority should be guided by fairness, self-control, clear rules, and respect for people.", "Community trust is essential. People are more likely to ask for help, report problems, and cooperate when they believe they will be treated fairly. This makes communication and de-escalation important skills. A calm conversation can sometimes prevent a conflict from becoming worse.", "Discussing police work can raise strong opinions because communities have different experiences. That is why this topic is useful for older students: it requires respectful listening, careful language, and the ability to separate ideals, responsibilities, challenges, and real-world concerns.", "For Yuva Club, the leadership lesson is that power must be accountable. A presenter can explain one police responsibility, one community concern, and one way trust can be strengthened. The goal is thoughtful discussion, not slogans."],
    "questions": ["Why does authority require responsibility and accountability?", "How does community trust affect public safety?", "What communication skills can help reduce conflict?", "How can students discuss difficult public issues respectfully?", "What does fairness look like when rules must be enforced?"],
    "activity": "Create a short role-play showing how calm communication can reduce conflict."
  },
  {
    "slug": "volunteers",
    "month": "Topic 12",
    "category": "Community & Service",
    "type": "Service",
    "title": "Volunteers",
    "subtitle": "Helping without expecting payment, building community, and learning service.",
    "readTime": "6 min",
    "skill": "Service Mindset",
    "vocabulary": ["volunteer", "service", "community", "commitment", "empathy", "initiative", "impact"],
    "reading": ["Volunteers give time, effort, and care to help others or improve a community. They may serve at food banks, temples, schools, hospitals, libraries, parks, cultural events, animal shelters, or nonprofit programs. Volunteering teaches that leadership is not always a title. Sometimes it is simply showing up and helping.", "Service builds empathy because volunteers learn about needs beyond their own daily life. They may see hunger, loneliness, environmental problems, educational gaps, or community events that require many helping hands. This can change how students understand responsibility.", "Good volunteering also requires reliability. A volunteer who signs up but does not show up can make work harder for everyone else. Leadership means keeping commitments, following instructions, respecting the people being served, and asking how to be useful.", "For Yuva Club, volunteering can connect to leadership hours, certificates, and student growth. A presenter can explain one volunteer organization or service experience and describe what skills it taught: teamwork, humility, time management, communication, or gratitude."],
    "questions": ["Why is showing up consistently part of service leadership?", "How can volunteering change the way students see their community?", "What is the difference between helping and trying to look helpful?", "How should volunteers respect the people they serve?", "What volunteer opportunity would fit Yuva Club students?"],
    "activity": "Find one realistic volunteer opportunity and explain the need, the task, and the skills students would practice."
  },
  {
    "slug": "nonprofit-leaders",
    "month": "Topic 12",
    "category": "Community & Service",
    "type": "Service",
    "title": "Nonprofit Leaders",
    "subtitle": "Mission, fundraising, teamwork, trust, and solving community problems.",
    "readTime": "6 min",
    "skill": "Mission-Driven Leadership",
    "vocabulary": ["nonprofit", "mission", "fundraising", "donor", "impact", "accountability", "community need"],
    "reading": ["Nonprofit organizations exist to serve a mission rather than to make profit for owners. They may work on education, health, hunger, arts, culture, environment, youth programs, disaster relief, or community development. Nonprofit leaders turn concern into organized action.", "A nonprofit leader must understand the problem, build a team, raise funds, manage volunteers, communicate with donors, and measure impact. This requires both heart and structure. Passion matters, but passion without planning can quickly become confusing.", "Trust is central to nonprofit work. Donors, volunteers, and communities need to believe that resources are being used responsibly. Leaders must be honest about goals, results, challenges, and finances. Accountability helps service remain meaningful.", "For Yuva Club, nonprofit leadership is a powerful topic because students can imagine starting small service projects. A presenter can choose a nonprofit, explain its mission, describe who it serves, and identify one leadership challenge it faces."],
    "questions": ["How is a nonprofit different from a business?", "Why does a mission need planning and measurement?", "How can nonprofit leaders build trust with donors and communities?", "What community problem would you want a nonprofit to solve?", "How could students start a small mission-driven project?"],
    "activity": "Design a simple nonprofit idea with a mission, audience, volunteer roles, and one way to measure impact."
  },
  {
    "slug": "everyday-leaders-community",
    "month": "Topic 12",
    "category": "Community & Service",
    "type": "Service",
    "title": "Everyday Leaders in My Community",
    "subtitle": "Seeing leadership in parents, neighbors, mentors, friends, and local helpers.",
    "readTime": "6 min",
    "skill": "Gratitude and Observation",
    "vocabulary": ["everyday leader", "mentor", "responsibility", "kindness", "service", "gratitude", "influence"],
    "reading": ["Leadership is not found only in famous people. Everyday leaders may be parents, grandparents, teachers, coaches, neighbors, volunteers, older siblings, friends, business owners, librarians, nurses, religious leaders, or community organizers. They may never appear in a textbook, but they shape real lives.", "Everyday leaders often lead through habits. They show up on time, help without being asked, solve problems calmly, include others, keep promises, and make people feel seen. These actions may look small, but repeated kindness and responsibility can create trust.", "This topic asks students to observe their own community carefully. Who makes life better for others? Who works quietly? Who encourages young people? Who stays honest when it is difficult? These questions train students to recognize character, not just fame.", "For Yuva Club, this can become a student presentation. Choose one person who has made a positive difference, explain their challenges and leadership qualities, and describe one lesson you will apply in your own life."],
    "questions": ["Why do we sometimes overlook everyday leaders?", "What habits make a person trustworthy over time?", "Who in your community leads without asking for attention?", "How can gratitude make us better leaders?", "What lesson from an everyday leader can you apply this week?"],
    "activity": "Prepare a 3-5 minute presentation about an everyday leader and write a thank-you note to that person."
  },
  {
    "slug": "public-speaking",
    "month": "Topic 13",
    "category": "Life Skills",
    "type": "Skill",
    "title": "Public Speaking",
    "subtitle": "Clear voice, strong structure, audience respect, and confident delivery.",
    "readTime": "6 min",
    "skill": "Confident Communication",
    "vocabulary": ["public speaking", "audience", "structure", "eye contact", "pace", "message", "confidence"],
    "reading": ["Public speaking is the skill of sharing ideas clearly with an audience. It is not only for stages and competitions. Students use public speaking when they present in class, answer questions, lead a meeting, explain a project, interview for opportunities, or speak up for a cause.", "A strong speech has structure. The speaker opens with the topic, explains key points in order, uses examples, and ends with a clear takeaway. Structure helps the audience follow the message, and it helps the speaker stay calm because they know where they are going.", "Delivery matters too. Voice, pace, eye contact, posture, and pauses can make a message easier to understand. Confidence does not mean never feeling nervous. Confidence means preparing well enough to speak even when nervous.", "For Yuva Club, public speaking is one of the main skills. Students should practice short presentations often, ask for feedback, and improve step by step. The goal is not perfection. The goal is to become clearer, braver, and more respectful each time."],
    "questions": ["What makes a speech easy or difficult to follow?", "How can a speaker show respect for the audience?", "What should students do when they feel nervous?", "Why is structure important in a 3-5 minute presentation?", "How can feedback help without discouraging the speaker?"],
    "activity": "Give a one-minute talk with an opening, two points, one example, and a closing sentence."
  },
  {
    "slug": "financial-literacy",
    "month": "Topic 13",
    "category": "Life Skills",
    "type": "Skill",
    "title": "Financial Literacy",
    "subtitle": "Saving, spending, budgeting, needs, wants, and responsible choices.",
    "readTime": "6 min",
    "skill": "Wise Decision Making",
    "vocabulary": ["financial literacy", "budget", "saving", "spending", "needs", "wants", "interest"],
    "reading": ["Financial literacy means understanding how money works and how to make responsible choices with it. Teenagers may not manage a household yet, but they already make money choices: saving gift money, comparing prices, avoiding waste, earning from small jobs, fundraising, or planning for college.", "A basic budget helps people decide where money should go. Some money may be needed for essentials, some for saving, some for giving, and some for wants. Learning the difference between needs and wants does not mean never enjoying life. It means making choices instead of letting impulse choose for us.", "Financial literacy is also connected to values. How we spend money can show priorities. Do we save for goals? Do we give to help others? Do we compare before buying? Do we understand the long-term cost of borrowing? These are leadership questions because money decisions affect freedom, stress, and responsibility.", "For Yuva Club, students can practice by creating a simple budget for an event, fundraiser, or personal goal. The lesson is not about becoming rich quickly. It is about becoming thoughtful, disciplined, and honest with resources."],
    "questions": ["Why should teenagers learn about money before they have major expenses?", "How do needs and wants differ, and can the line ever be unclear?", "What values can money choices reveal?", "How can budgeting reduce stress?", "What financial habit would help students in high school or college?"],
    "activity": "Create a simple budget for a Yuva Club event with income, expenses, savings, and one giving goal."
  },
  {
    "slug": "time-management",
    "month": "Topic 13",
    "category": "Life Skills",
    "type": "Skill",
    "title": "Time Management",
    "subtitle": "Priorities, planning, focus, deadlines, rest, and reliable habits.",
    "readTime": "6 min",
    "skill": "Prioritization",
    "vocabulary": ["time management", "priority", "deadline", "schedule", "focus", "procrastination", "balance"],
    "reading": ["Time management means using time intentionally instead of letting tasks pile up until stress takes over. Students need this skill for homework, activities, family responsibilities, presentations, exams, sleep, and friendships. Time is limited, so priorities matter.", "A good plan begins by naming what must be done, when it is due, and how long it may take. Large tasks become easier when divided into smaller steps. For example, a presentation can be split into choosing a topic, researching, outlining, practicing, and preparing slides.", "Time management is not only about working more. It also includes rest. A tired, rushed student may make more mistakes and feel less confident. Leaders learn to plan ahead so they can do quality work without panic.", "For Yuva Club, time management helps students become reliable presenters. If a student waits until the last minute, the whole group may lose learning time. When students prepare early, they respect their audience and build confidence."],
    "questions": ["Why do students often procrastinate even when they care about the task?", "How can breaking a big task into small steps reduce stress?", "Why should rest be part of time management?", "How does being late or unprepared affect a team?", "What planning habit would help your school week immediately?"],
    "activity": "Make a five-day preparation plan for a 3-5 minute presentation."
  },
  {
    "slug": "entrepreneurship-life-skill",
    "month": "Topic 13",
    "category": "Life Skills",
    "type": "Skill",
    "title": "Entrepreneurship",
    "subtitle": "Finding problems, testing ideas, serving customers, and learning from failure.",
    "readTime": "6 min",
    "skill": "Initiative",
    "vocabulary": ["entrepreneurship", "customer", "problem", "prototype", "risk", "feedback", "value"],
    "reading": ["Entrepreneurship is the skill of noticing a problem or opportunity and creating something useful in response. Entrepreneurs may start companies, apps, services, community projects, creative products, or social ventures. The heart of entrepreneurship is value: helping someone solve a real need.", "A beginner entrepreneur should not start with bragging about an idea. They should ask questions. Who has the problem? How serious is it? What solutions already exist? What would make this better? Feedback helps improve the idea before too much time or money is spent.", "Entrepreneurship also teaches failure in a healthy way. Not every idea works. A test may show that people do not need the product, the cost is too high, or the design is confusing. A good entrepreneur learns, changes, and tries again with better information.", "For Yuva Club, entrepreneurship can be practiced through small projects: a fundraiser, tutoring service, student newsletter, cultural event, or simple product idea. Students should focus on solving real problems ethically and communicating clearly."],
    "questions": ["What is the difference between an idea and a useful solution?", "Why should entrepreneurs listen before building?", "How can failure provide information instead of shame?", "What ethical responsibilities do entrepreneurs have?", "What problem could students solve in their school or community?"],
    "activity": "Create a one-page idea pitch: problem, audience, solution, feedback question, and first small test."
  },
  {
    "slug": "teamwork-life-skill",
    "month": "Topic 13",
    "category": "Life Skills",
    "type": "Skill",
    "title": "Teamwork",
    "subtitle": "Roles, trust, listening, contribution, conflict, and shared success.",
    "readTime": "6 min",
    "skill": "Collaboration",
    "vocabulary": ["teamwork", "role", "trust", "collaboration", "conflict", "accountability", "shared goal"],
    "reading": ["Teamwork means people working together toward a shared goal. A team can be a school project group, sports team, family, volunteer group, club committee, or research team. Good teamwork does not happen automatically just because people are placed together.", "Teams need clear roles, honest communication, and trust. Some members may research, some design slides, some speak, some organize, and some check details. When roles are unclear, work may be repeated or forgotten. When trust is low, people may avoid responsibility or blame others.", "Conflict is normal in teams. The goal is not to pretend everyone agrees. The goal is to disagree respectfully, listen for the best idea, and keep the shared mission above personal pride. Strong teams solve problems without damaging relationships.", "For Yuva Club, teamwork matters when students plan presentations, discussions, service projects, or events. A good team member contributes, listens, meets deadlines, encourages others, and gives credit. Shared success is stronger than one person's spotlight."],
    "questions": ["Why do teams need clear roles?", "What breaks trust in a group project?", "How can disagreement improve a team instead of hurting it?", "What does accountability look like for students?", "How can a team give credit fairly?"],
    "activity": "Assign roles for a mini group presentation: researcher, speaker, question leader, and timekeeper."
  },
  {
    "slug": "communication-life-skill",
    "month": "Topic 13",
    "category": "Life Skills",
    "type": "Skill",
    "title": "Communication",
    "subtitle": "Listening, clarity, questions, tone, feedback, and understanding.",
    "readTime": "6 min",
    "skill": "Active Listening",
    "vocabulary": ["communication", "listening", "tone", "feedback", "clarity", "question", "misunderstanding"],
    "reading": ["Communication is more than talking. It includes listening, asking questions, choosing words carefully, noticing tone, and checking whether the other person understood. Many problems in school, teams, and families begin with unclear or incomplete communication.", "Active listening means giving attention to the speaker and trying to understand before responding. It may include summarizing what you heard, asking clarifying questions, and not interrupting. Listening is a leadership skill because people trust leaders who make them feel heard.", "Clear communication also requires courage. Sometimes students need to ask for help, admit confusion, apologize, disagree respectfully, or give feedback. These moments are not always comfortable, but they prevent small problems from becoming larger.", "For Yuva Club, communication shapes every session. Presenters explain ideas, listeners ask questions, mentors guide discussion, and students learn from one another. The club becomes stronger when students practice speaking clearly and listening generously."],
    "questions": ["Why is listening part of communication, not separate from it?", "How can tone change the meaning of words?", "What should students do when they do not understand something?", "How can feedback be honest and kind at the same time?", "What communication habit would improve Yuva Club discussions?"],
    "activity": "Practice active listening: one student speaks for one minute, and another summarizes the message before responding."
  },
  {
    "slug": "goal-setting",
    "month": "Topic 13",
    "category": "Life Skills",
    "type": "Skill",
    "title": "Goal Setting",
    "subtitle": "Dreams, plans, habits, measurement, resilience, and follow-through.",
    "readTime": "6 min",
    "skill": "Follow-Through",
    "vocabulary": ["goal", "habit", "milestone", "progress", "resilience", "deadline", "reflection"],
    "reading": ["Goal setting turns a wish into a plan. Many students say they want to become better speakers, earn good grades, learn an instrument, volunteer more, build a project, or improve fitness. A goal becomes stronger when it is specific, realistic, and connected to daily habits.", "Large goals need milestones. If a student wants to become a confident presenter, the milestones might be choosing topics, practicing weekly, recording a speech, asking for feedback, and presenting several times. Progress becomes easier to see when it is measured.", "Goal setting also includes resilience. Plans do not always work perfectly. Students may miss a practice day, feel nervous, or receive difficult feedback. A leader does not quit after one setback. A leader reflects, adjusts, and continues.", "For Yuva Club, goal setting can help students build a leadership portfolio over time. Each presentation, research submission, service hour, and certificate can become part of a larger story of growth."],
    "questions": ["What is the difference between a wish and a goal?", "Why do goals need habits, not only motivation?", "How should students respond when they fall behind?", "What is one goal Yuva Club can help you reach?", "How can tracking progress build confidence?"],
    "activity": "Write one 30-day leadership goal with three milestones and one weekly habit."
  }
]
'@

$architectureSpaceItemsJson = @'
[
  {
    "slug": "taj-mahal",
    "month": "Topic 7",
    "category": "Great Monuments & Architecture",
    "type": "Monument",
    "title": "Taj Mahal",
    "subtitle": "A marble monument of memory, design, symmetry, craftsmanship, and cultural heritage.",
    "readTime": "6 min",
    "skill": "Attention to Detail",
    "vocabulary": ["mausoleum", "symmetry", "marble", "craftsmanship", "heritage", "calligraphy", "architecture"],
    "reading": ["The Taj Mahal in Agra, India, is one of the world's most admired monuments. It was built in the 17th century by Mughal emperor Shah Jahan in memory of Mumtaz Mahal. The complex includes the white marble mausoleum, gardens, gateways, water channels, and surrounding buildings. Its beauty comes not from one feature, but from the harmony of many parts.", "Students can study the Taj Mahal as an example of architecture, art, engineering, and planning. Its symmetry, marble inlay work, calligraphy, dome, minarets, and garden layout show how design choices can create a feeling of balance and peace. A monument like this also required many skilled workers, materials, organization, and long-term commitment.", "The Taj Mahal also raises questions about memory and heritage. Why do people build monuments? What should a monument help us remember? How should societies protect historic places when millions of visitors want to see them? Preservation requires care because pollution, crowding, weather, and time can damage even strong buildings.", "For Yuva Club, the Taj Mahal is not only a beautiful building. It is a lesson in vision, detail, teamwork, and cultural responsibility. A presenter should explain what the monument is, why it was built, how design creates meaning, and why protecting heritage matters."],
    "questions": ["How do symmetry and design affect the way people feel when they see a building?", "Why do societies build monuments to remember people or events?", "What responsibilities do visitors and governments have toward famous historic sites?", "How can art, engineering, and leadership work together in one project?", "Should a monument be judged only by beauty, or also by history and meaning?"],
    "activity": "Sketch a simple memorial design and explain three choices: material, shape, and message."
  },
  {
    "slug": "great-wall-of-china",
    "month": "Topic 7",
    "category": "Great Monuments & Architecture",
    "type": "Monument",
    "title": "Great Wall of China",
    "subtitle": "Defense, borders, labor, strategy, communication, and historical memory.",
    "readTime": "6 min",
    "skill": "Strategic Planning",
    "vocabulary": ["fortification", "watchtower", "dynasty", "border", "defense", "communication", "strategy"],
    "reading": ["The Great Wall of China is not one single wall built at one time. It is a connected system of walls, watchtowers, passes, and fortifications built and rebuilt across different dynasties. Sections of the wall crossed mountains, deserts, and grasslands. The wall is often studied as a symbol of defense, organization, and long-term national effort.", "The Great Wall teaches students about strategy. A wall alone cannot protect a civilization. It also needs soldiers, watchtowers, signals, roads, supplies, and leadership. The wall helped with border defense and communication across difficult terrain. It shows how architecture can be part of a larger system.", "This monument also invites honest discussion. Large projects can require sacrifice, labor, taxes, and difficult decisions. Students should ask not only what was built, but who built it, why it was needed, and what costs came with it. Great achievements often include complicated human stories.", "For Yuva Club, the Great Wall is a topic about planning at scale. A presenter can explain how geography shaped the wall, how watchtowers helped communication, and how leaders balance security, resources, and human cost."],
    "questions": ["Why is the Great Wall better understood as a system rather than just a wall?", "How did geography affect the design and difficulty of building it?", "What are the benefits and costs of large public projects?", "How can leaders balance safety with human cost?", "What modern systems help communities protect and communicate across long distances?"],
    "activity": "Design a simple defense and communication plan for a mountain border using towers, roads, signals, and supply points."
  },
  {
    "slug": "pyramids-of-giza",
    "month": "Topic 7",
    "category": "Great Monuments & Architecture",
    "type": "Monument",
    "title": "Pyramids of Giza",
    "subtitle": "Ancient engineering, organization, mathematics, belief, and leadership at scale.",
    "readTime": "6 min",
    "skill": "Organized Effort",
    "vocabulary": ["pyramid", "pharaoh", "limestone", "engineering", "alignment", "labor", "ancient Egypt"],
    "reading": ["The Pyramids of Giza in Egypt are among the most famous ancient monuments in the world. Built during Egypt's Old Kingdom, they were connected to royal burial practices and beliefs about the afterlife. The Great Pyramid, associated with Pharaoh Khufu, remains an extraordinary example of ancient engineering and organization.", "Students can study the pyramids through many lenses: mathematics, stone cutting, transportation, alignment, labor organization, religion, and government power. Moving and placing huge stones required planning, tools, skilled workers, food supplies, leadership, and record keeping. The pyramids show what a society can build when many people work toward a single goal.", "The pyramids also teach humility about ancient knowledge. People in the past did not have modern machines, but they had observation, skill, patience, and systems. A strong presentation should avoid saying ancient people were primitive. Instead, students should ask how they solved problems with the knowledge and resources available.", "For Yuva Club, the Pyramids of Giza are a lesson in organized effort. Students can discuss how vision becomes reality only when planning, labor, technical skill, and shared belief come together."],
    "questions": ["What skills and systems were needed to build the pyramids?", "Why should we avoid underestimating ancient civilizations?", "How can belief or culture motivate huge building projects?", "What does organized effort look like in a modern student project?", "How do monuments help historians learn about societies?"],
    "activity": "Create a project plan for building a pyramid model: roles, materials, timeline, measurements, and quality checks."
  },
  {
    "slug": "machu-picchu",
    "month": "Topic 7",
    "category": "Great Monuments & Architecture",
    "type": "Monument",
    "title": "Machu Picchu",
    "subtitle": "Mountain architecture, Inca planning, terraces, stonework, and adaptation.",
    "readTime": "6 min",
    "skill": "Adaptation",
    "vocabulary": ["Inca", "terrace", "Andes", "stonework", "citadel", "drainage", "adaptation"],
    "reading": ["Machu Picchu is an Inca site in the Andes Mountains of Peru. It is famous for its dramatic mountain setting, stone buildings, terraces, and careful planning. The site shows how architecture can work with the land instead of ignoring it. Builders had to consider steep slopes, rainfall, stone materials, paths, and water management.", "One of Machu Picchu's most important lessons is adaptation. The Inca used terraces to manage slopes and support agriculture, and they built stone structures with remarkable precision. Drainage was essential because mountain rain can damage buildings and cause erosion. Good design solved practical problems while also creating beauty.", "Machu Picchu also raises preservation questions. Many people want to visit famous heritage sites, but too much tourism can stress fragile places. Leaders must protect the site while allowing people to learn from it. This requires rules, research, maintenance, and respect from visitors.", "For Yuva Club, Machu Picchu connects engineering, environment, culture, and responsibility. A presenter should explain how mountain challenges shaped the design and how modern people can enjoy heritage without damaging it."],
    "questions": ["How did the mountain environment shape Machu Picchu's design?", "Why are drainage and terraces important in mountain architecture?", "How can tourism help and harm heritage sites?", "What does it mean to build with nature instead of against it?", "How can adaptation become a leadership skill?"],
    "activity": "Design a small hillside community and include terraces, water flow, paths, and safety features."
  },
  {
    "slug": "angkor-wat",
    "month": "Topic 7",
    "category": "Great Monuments & Architecture",
    "type": "Monument",
    "title": "Angkor Wat",
    "subtitle": "Temple architecture, water systems, art, devotion, and Khmer civilization.",
    "readTime": "6 min",
    "skill": "Integrated Design",
    "vocabulary": ["Angkor Wat", "Khmer", "temple", "moat", "bas-relief", "heritage", "water management"],
    "reading": ["Angkor Wat in Cambodia is one of the world's great temple complexes and a masterpiece of Khmer architecture. It was originally built in the 12th century and is known for its towers, galleries, moat, carvings, and symbolic design. The site reflects religion, kingship, art, engineering, and cultural identity.", "Students can study Angkor Wat as integrated design. Its layout, water features, carvings, and architecture work together to communicate meaning. The bas-reliefs tell stories and show skillful artistry, while the moat and surrounding landscape remind us that large monuments often depend on water management and planning.", "Angkor also teaches that cities and monuments are connected. A temple does not stand alone from the society that builds it. Workers, artists, engineers, rulers, farmers, and water systems all support such a place. Understanding a monument means asking how the whole civilization made it possible.", "For Yuva Club, Angkor Wat is a topic about combining art, engineering, and ideas. A presenter can explain how the design expresses meaning, how water systems supported the area, and why heritage protection matters today."],
    "questions": ["How can architecture communicate ideas or beliefs?", "Why are water systems important when studying Angkor Wat?", "What roles besides rulers are needed to create a monument?", "How do carvings and art help preserve stories?", "What does integrated design look like in a modern building or campus?"],
    "activity": "Design a symbolic building entrance and explain how shape, water, art, and pathways communicate a message."
  },
  {
    "slug": "isro",
    "month": "Topic 8",
    "category": "Space & Technology",
    "type": "Technology",
    "title": "ISRO",
    "subtitle": "India's space agency, satellites, launch vehicles, teamwork, and technology for society.",
    "readTime": "6 min",
    "skill": "Purposeful Innovation",
    "vocabulary": ["ISRO", "satellite", "launch vehicle", "mission", "remote sensing", "communication", "innovation"],
    "reading": ["ISRO, the Indian Space Research Organisation, is India's national space agency. It develops satellites, launch vehicles, space missions, and applications that support communication, weather, navigation, disaster management, education, agriculture, and scientific exploration. Space technology is not only about rockets; it can improve life on Earth.", "ISRO's story teaches purposeful innovation. A space agency must combine physics, engineering, software, materials, communication, project management, and teamwork. Missions take years of planning and testing. Many people contribute, including scientists, engineers, technicians, administrators, and mission controllers.", "ISRO is also a strong leadership topic because it shows how a country can use science for public benefit. Satellites can help track storms, map resources, support phones and television, assist navigation, and study Earth. Students can discuss how technology should serve society, not only impress people.", "For Yuva Club, an ISRO presentation can explain one mission or one application of satellites. The key question is: how can big technology solve real problems for ordinary people?"],
    "questions": ["Why is space technology useful for life on Earth?", "What kinds of teamwork are needed for a space mission?", "How can a space agency serve society beyond exploration?", "Why does testing matter before a launch?", "What problem would you want satellite technology to help solve?"],
    "activity": "Choose one satellite use, such as weather, navigation, communication, or disaster response, and explain its public benefit."
  },
  {
    "slug": "chandrayaan",
    "month": "Topic 8",
    "category": "Space & Technology",
    "type": "Technology",
    "title": "Chandrayaan",
    "subtitle": "India's Moon missions, scientific curiosity, landing challenges, and national confidence.",
    "readTime": "6 min",
    "skill": "Persistence",
    "vocabulary": ["Chandrayaan", "lunar", "orbiter", "lander", "rover", "payload", "mission control"],
    "reading": ["Chandrayaan is India's lunar exploration program. The name means moon craft in Sanskrit. These missions study the Moon through orbiters, landers, rovers, and scientific instruments. Moon missions help scientists understand the lunar surface, minerals, temperature, geology, and the history of the Earth-Moon system.", "Chandrayaan teaches students that exploration requires patience and persistence. A lunar mission must survive launch, travel through space, enter the correct path, communicate with Earth, and operate in a harsh environment. Landing on the Moon is especially difficult because timing, speed, navigation, software, and hardware must work together.", "The Chandrayaan program also shows how setbacks can become learning. Space missions are complex, and not every attempt goes perfectly. Strong teams study what happened, improve designs, and try again. This is a powerful lesson for students who fear mistakes.", "For Yuva Club, Chandrayaan connects science with courage. A presenter can explain one Chandrayaan mission, one scientific goal, one engineering challenge, and one leadership lesson about persistence and teamwork."],
    "questions": ["Why is landing on the Moon so difficult?", "What can students learn from scientific setbacks?", "How do orbiters, landers, and rovers do different jobs?", "Why should countries invest in exploration and science?", "How can persistence be different from repeating the same mistake?"],
    "activity": "Create a simple lunar mission plan with a goal, spacecraft type, instrument, challenge, and success measure."
  },
  {
    "slug": "apollo-missions",
    "month": "Topic 8",
    "category": "Space & Technology",
    "type": "Technology",
    "title": "Apollo Missions",
    "subtitle": "Moon landings, teamwork, risk, mission control, and one giant leap for exploration.",
    "readTime": "6 min",
    "skill": "Team Leadership",
    "vocabulary": ["Apollo", "astronaut", "mission control", "lunar module", "orbit", "risk", "exploration"],
    "reading": ["NASA's Apollo missions were a series of human spaceflight missions that led to astronauts landing on the Moon. Apollo 11 became famous in 1969 when Neil Armstrong and Buzz Aldrin walked on the lunar surface while Michael Collins remained in lunar orbit. The missions represented engineering, courage, planning, and teamwork at a historic scale.", "Apollo was not only about three astronauts in a spacecraft. Thousands of people worked on rockets, computers, spacesuits, navigation, medicine, training, communication, and mission control. This reminds students that public achievements often depend on many people whose names are not widely known.", "The Apollo missions also involved risk. Spaceflight is dangerous, and leaders had to make decisions under pressure. Mission control teams practiced many scenarios, because preparation can save lives. The Apollo story is therefore a lesson in courage supported by discipline.", "For Yuva Club, Apollo can be presented as a teamwork story. Students should explain the mission goal, the roles of astronauts and ground teams, one technical challenge, and one leadership lesson about preparation and shared success."],
    "questions": ["Why was Apollo a team achievement rather than only an astronaut achievement?", "How does preparation reduce risk in difficult missions?", "What leadership qualities are needed in mission control?", "Why do exploration goals inspire people?", "How should teams give credit when many people contribute?"],
    "activity": "Assign a mock mission team with roles: astronaut, engineer, doctor, communicator, and mission director. Explain each role."
  },
  {
    "slug": "artificial-intelligence",
    "month": "Topic 8",
    "category": "Space & Technology",
    "type": "Technology",
    "title": "Artificial Intelligence",
    "subtitle": "Learning machines, data, decisions, creativity, ethics, and responsible use.",
    "readTime": "6 min",
    "skill": "Ethical Thinking",
    "vocabulary": ["artificial intelligence", "algorithm", "data", "model", "bias", "automation", "ethics"],
    "reading": ["Artificial intelligence, or AI, refers to computer systems that can perform tasks that seem to require human intelligence, such as recognizing patterns, answering questions, translating language, recommending videos, detecting fraud, helping doctors, or guiding robots. AI works by using data, algorithms, and models to make predictions or generate outputs.", "AI can be useful, but it is not magic. It can make mistakes, reflect bias in data, misunderstand context, or produce answers that sound confident but are wrong. This is why students must learn to use AI responsibly. A good user checks sources, protects privacy, thinks critically, and does not let tools replace their own learning.", "AI also raises ethical questions. Who is responsible when AI makes a harmful recommendation? How should schools handle AI writing tools? How do we protect jobs, creativity, privacy, and fairness? These questions require more than technical knowledge. They require values and leadership.", "For Yuva Club, AI is a powerful presentation topic because teenagers already interact with it. The goal is to understand how AI can help people while also learning when to question it, limit it, or use it with guidance."],
    "questions": ["What is one helpful use of AI and one possible risk?", "Why should students verify AI-generated information?", "How can data bias affect AI decisions?", "What should schools encourage or limit about AI tools?", "What does responsible AI use look like for a student presenter?"],
    "activity": "Compare an AI answer with two trusted sources and identify what was accurate, missing, or questionable."
  },
  {
    "slug": "robotics",
    "month": "Topic 8",
    "category": "Space & Technology",
    "type": "Technology",
    "title": "Robotics",
    "subtitle": "Machines that sense, move, assist, explore, build, and solve real-world problems.",
    "readTime": "6 min",
    "skill": "Problem Solving",
    "vocabulary": ["robotics", "sensor", "actuator", "programming", "automation", "prototype", "engineering"],
    "reading": ["Robotics is the field of designing, building, programming, and using robots. A robot may sense its environment, process information, move parts, and perform tasks. Robots are used in factories, hospitals, homes, agriculture, disaster response, ocean exploration, and space missions.", "A robot is a problem-solving system. Sensors help it notice the world, software helps it decide what to do, and actuators or motors help it act. Students can understand robotics by asking: What problem is the robot solving? What information does it need? What actions must it perform? What could go wrong?", "Robotics also teaches iteration. Engineers often build prototypes, test them, discover problems, and improve the design. A robot might fail because the sensor is confused, the code has an error, the wheels slip, or the task is harder than expected. Each failure gives information for the next version.", "For Yuva Club, robotics connects creativity with practical thinking. A presenter can explain one robot, the problem it solves, the sensors it uses, and the ethical questions it raises. Robots should be judged not only by how cool they look, but by whether they help people responsibly."],
    "questions": ["What makes a machine a robot?", "How do sensors, software, and movement work together?", "Why is testing important in robotics?", "What jobs are best suited for robots, and what jobs still need humans?", "What ethical questions should robotics designers consider?"],
    "activity": "Design a robot for school, home, space, health, or disaster response. Explain its sensors, actions, and safety rules."
  }
]
'@

$growthItems = ConvertFrom-Json -InputObject $growthItemsJson
$architectureSpaceItems = ConvertFrom-Json -InputObject $architectureSpaceItemsJson
$items = @($items + $civilizationItems + $growthItems + $architectureSpaceItems)

foreach ($item in $items) {
  if ($item.slug -in @("kalam-childhood", "kalam-scientist", "kalam-president", "vivekananda-childhood", "vivekananda-chicago", "vivekananda-service", "mahatma-gandhi", "mother-teresa", "rani-lakshmibai", "subhas-chandra-bose", "sardar-patel", "chanakya", "leadership-around-us")) {
    $item.category = "Great Leaders"
  }
  elseif ($item.slug -in @("abraham-lincoln", "martin-luther-king-jr", "nelson-mandela")) {
    $item.category = "World Leaders"
  }
  elseif ($item.slug -in @("aryabhata", "srinivasa-ramanujan", "wright-brothers", "kalpana-chawla", "cv-raman", "homi-bhabha", "vikram-sarabhai", "katherine-johnson", "albert-einstein")) {
    $item.category = "Scientists & Inventors"
  }
  elseif ($item.slug -in @("steve-jobs", "elon-musk", "bill-gates", "sundar-pichai", "satya-nadella", "jensen-huang", "larry-page-sergey-brin", "dhirubhai-ambani", "narayana-murthy", "ratan-tata", "kiran-mazumdar-shaw", "nandan-nilekani")) {
    $item.category = "Entrepreneurs & Innovators"
  }
  elseif ($item.category -eq "Hindu Traditions") {
    $item.category = "Indian Heritage"
  }
  elseif ($item.category -in @("Mahabharata", "Ramayana", "Panchatantra")) {
    $item.category = "Leadership Lessons from Stories"
  }
}

function Escape-Html([string]$value) {
  if ($null -eq $value) { return "" }
  return [System.Net.WebUtility]::HtmlEncode($value)
}

function Section-Label([string]$value) {
  if ($null -eq $value) { return "" }
  return ($value -replace "^Month ", "Section ")
}

function Category-Order([string]$category) {
  switch ($category) {
    "Leadership & Inspiration" { 1 }
    "Science & Technology" { 2 }
    "Business & Entrepreneurship" { 3 }
    "History & Civilization" { 4 }
    "Geography & Cultures" { 5 }
    "Architecture & Engineering" { 6 }
    "Environment" { 7 }
    "Health & Wellness" { 8 }
    "Books & Literature" { 9 }
    "Arts & Creativity" { 10 }
    "Sports" { 11 }
    "Digital Skills" { 12 }
    "Communication" { 13 }
    "Character Development" { 14 }
    "Community & Service" { 15 }
    "STEM Challenges" { 16 }
    "Career Exploration" { 17 }
    default { 99 }
  }
}

function Category-Section-Label([string]$category) {
  switch ($category) {
    "Leadership & Inspiration" { "Global Topic 1" }
    "Science & Technology" { "Global Topic 2" }
    "Business & Entrepreneurship" { "Global Topic 3" }
    "History & Civilization" { "Global Topic 4" }
    "Geography & Cultures" { "Global Topic 5" }
    "Architecture & Engineering" { "Global Topic 6" }
    "Environment" { "Global Topic 7" }
    "Health & Wellness" { "Global Topic 8" }
    "Books & Literature" { "Global Topic 9" }
    "Arts & Creativity" { "Global Topic 10" }
    "Sports" { "Global Topic 11" }
    "Digital Skills" { "Global Topic 12" }
    "Communication" { "Global Topic 13" }
    "Character Development" { "Global Topic 14" }
    "Community & Service" { "Global Topic 15" }
    "STEM Challenges" { "Global Topic 16" }
    "Career Exploration" { "Global Topic 17" }
    default { "" }
  }
}

function All-Categories() {
  return @(
    "Leadership & Inspiration",
    "Science & Technology",
    "Business & Entrepreneurship",
    "History & Civilization",
    "Geography & Cultures",
    "Architecture & Engineering",
    "Environment",
    "Health & Wellness",
    "Books & Literature",
    "Arts & Creativity",
    "Sports",
    "Digital Skills",
    "Communication",
    "Character Development",
    "Community & Service",
    "STEM Challenges",
    "Career Exploration"
  )
}

function Slug-Class([string]$value) {
  return ($value.ToLower() -replace "[^a-z0-9]+", "-").Trim("-")
}

function Normalize-Global-Category([string]$category) {
  switch ($category) {
    "Great Leaders" { "Leadership & Inspiration" }
    "World Leaders" { "Leadership & Inspiration" }
    "Scientists & Inventors" { "Science & Technology" }
    "Space & Technology" { "Science & Technology" }
    "Innovators, Entrepreneurs & Changemakers" { "Business & Entrepreneurship" }
    "Entrepreneurs & Innovators" { "Business & Entrepreneurship" }
    "Explorers & Adventurers" { "History & Civilization" }
    "Ancient Civilizations" { "History & Civilization" }
    "Great Monuments & Architecture" { "Architecture & Engineering" }
    "Indian Heritage" { "Geography & Cultures" }
    "Hindu Traditions" { "Geography & Cultures" }
    "Leadership Lessons from Stories" { "Books & Literature" }
    "Mahabharata" { "Books & Literature" }
    "Ramayana" { "Books & Literature" }
    "Panchatantra" { "Books & Literature" }
    "Environment & Nature" { "Environment" }
    "Life Skills" { "Character Development" }
    "Everyday Leaders" { "Community & Service" }
    default { $category }
  }
}

function New-GlobalTopicItem([string]$category, [string]$title, [string]$subtitle, [string]$skill, [string[]]$vocabulary) {
  $slug = Slug-Class "$category-$title"
  $reading = switch ($category) {
    "Health & Wellness" {
      @(
        "$title is part of health and wellness because students learn best when the body, mind, habits, and daily choices work together. Good health is not about perfection or comparing bodies. It is about understanding what helps a person have energy, focus, confidence, and resilience.",
        "Reliable health sources emphasize balance, safety, and steady habits. For example, students can study how food, movement, rest, stress management, and routines affect learning and mood. They can also discuss how families and cultures may practice wellness differently while still caring about the same goals: strength, calmness, and well-being.",
        "A strong presentation on $title should explain the science in simple language, avoid giving medical advice, and focus on practical choices students can discuss with parents, teachers, or health professionals. The presenter should ask how wellness habits influence school, friendships, sports, public speaking, and leadership.",
        "The leadership lesson is $skill. Healthy leaders pay attention to their habits, respect their limits, and encourage others without judgment. Students can use this topic to practice thoughtful communication about health, privacy, evidence, and personal responsibility."
      )
    }
    "Arts & Creativity" {
      @(
        "$title belongs to arts and creativity because creative work helps people observe, imagine, communicate, and express ideas that may be difficult to explain only with facts. Art can preserve culture, challenge assumptions, tell stories, and help communities understand themselves.",
        "Arts education includes visual arts, music, dance, theatre, literary arts, media arts, and design. A student presenter can explore the tools, history, techniques, audience, and purpose behind a creative form. The goal is not only to say whether something is beautiful, but to ask what it communicates and how it was made.",
        "A strong presentation on $title should include examples from different parts of the world. Students can compare styles, explain the role of practice, and describe how creators use choices such as color, sound, movement, framing, rhythm, space, or performance to create meaning.",
        "The leadership lesson is $skill. Creative leaders learn to revise, accept feedback, solve problems, and share a point of view. Yuva Club students can practice describing art respectfully, asking open-ended questions, and noticing how creativity can serve people and communities."
      )
    }
    "Sports" {
      @(
        "$title is a useful sports topic because sports are not only about winning. They also teach preparation, teamwork, discipline, fairness, emotional control, health, and respect for rules. International events such as the Olympic Games and World Cup show how sports can bring people together across countries and cultures.",
        "A student presenter can study the history of a sport, a major event, an athlete, a team, or a scientific question such as training, nutrition, injury prevention, or performance. The best sports presentations balance excitement with thoughtful discussion about effort, pressure, ethics, and sportsmanship.",
        "Sports also create leadership questions. How should a captain encourage teammates? How should players respond to losing? What does fair play require when no one is watching? How can athletes use attention responsibly? These questions help students connect sports to character.",
        "The leadership lesson is $skill. Students should show how $title teaches self-control, respect, perseverance, and shared responsibility. A good Yuva Club discussion should recognize achievement while also valuing growth, effort, and teamwork."
      )
    }
    "Digital Skills" {
      @(
        "$title is part of digital skills because students live in a world shaped by devices, networks, apps, media, and artificial intelligence. Digital leadership means using technology thoughtfully, safely, creatively, and responsibly.",
        "A strong presentation should explain what the skill is, where people use it, and what risks or responsibilities come with it. Topics such as cybersecurity and internet safety require practical habits: strong passwords, privacy awareness, careful sharing, recognizing scams, and asking trusted adults for help when something feels unsafe.",
        "Digital citizenship also includes respect. Students should think about how their online choices affect others, how misinformation spreads, how creative work should be credited, and how AI tools should be used honestly. Technology is powerful, so ethical judgment matters.",
        "The leadership lesson is $skill. Students can use $title to practice problem solving, careful research, responsible creativity, and clear explanations. A good presenter should leave classmates with one safe digital habit they can use immediately."
      )
    }
    "Communication" {
      @(
        "$title is central to Yuva Club because leadership depends on communication. A leader must explain ideas clearly, listen carefully, ask thoughtful questions, and respond with respect. Communication is not only speaking loudly; it includes structure, evidence, tone, body language, and empathy.",
        "A student presenter can explore how this skill appears in school, interviews, debates, group projects, storytelling, public service, and careers. Good communication helps people understand complex ideas and work through disagreements without attacking one another.",
        "The best presentations on $title should include a short demonstration. Students might compare a weak opening with a strong opening, show how body language changes a message, or practice turning a closed question into an open-ended discussion question.",
        "The leadership lesson is $skill. Communication grows through practice, feedback, and reflection. In Yuva Club, students should learn to speak with confidence, listen with humility, and use words to build trust."
      )
    }
    "STEM Challenges" {
      @(
        "$title belongs to STEM challenges because it asks students to learn by doing. STEM is not only memorizing science or math facts. It is a process of asking questions, designing, testing, measuring, improving, and explaining results.",
        "A strong presentation should describe the challenge, the materials or tools, the rules, and the thinking process. Students should explain what they tried first, what failed, what changed, and what evidence helped them improve. This makes the presentation more honest and useful.",
        "STEM challenges also teach teamwork. One student may build, another may measure, another may record data, and another may present results. Good teams communicate clearly and treat mistakes as information rather than embarrassment.",
        "The leadership lesson is $skill. Students can use $title to practice curiosity, persistence, design thinking, and evidence-based discussion. The goal is to become comfortable solving problems step by step."
      )
    }
    "Career Exploration" {
      @(
        "$title is a career exploration topic because students benefit from learning what different professionals actually do, what skills they use, what education or training may be required, and how their work serves people or solves problems.",
        "A strong career presentation should go beyond salary or status. Students should explain daily responsibilities, required strengths, challenges, teamwork, ethics, and future opportunities. They can also interview a trusted adult, use official career resources, or compare similar careers.",
        "Career exploration helps students connect school subjects to real life. Math, science, writing, art, communication, technology, and service can all become part of meaningful work. Students should notice that most careers require learning, practice, responsibility, and collaboration.",
        "The leadership lesson is $skill. Students can use $title to ask what kind of contribution they want to make, what habits they need to build, and how a career can connect personal interests with service to others."
      )
    }
    default {
      @(
        "$title is a global Yuva Club topic that helps students research, present, discuss, and lead.",
        "A strong presentation should explain the background, key ideas, real-world examples, and why the topic matters today.",
        "Students should connect the topic to leadership, communication, responsibility, and practical action."
      )
    }
  }

  [pscustomobject]@{
    slug = $slug
    month = Category-Section-Label $category
    category = $category
    type = "Topic"
    title = $title
    subtitle = $subtitle
    readTime = "5 min"
    skill = $skill
    vocabulary = $vocabulary
    reading = $reading
    questions = @(
      "Why does $title matter for students today?",
      "What is one real-life example of $title?",
      "What responsibility or ethical question connects to this topic?",
      "How can students practice the leadership lesson from this topic?",
      "What question would you ask an expert about $title?"
    )
    activity = "Prepare a 3-5 minute presentation with one example, one discussion question, and one practical action students can try."
  }
}

$globalTopicSpecs = @(
  @{ Category = "Health & Wellness"; Topics = @(
    @("Nutrition", "Food choices, energy, balanced meals, culture, and healthy growth.", "Balanced Choices", @("nutrition", "balance", "energy", "fiber", "moderation")),
    @("Exercise", "Movement, fitness, strength, focus, teamwork, and daily activity.", "Active Habits", @("exercise", "fitness", "endurance", "strength", "sedentary")),
    @("Mental Well-being", "Stress, emotions, support, resilience, and asking for help.", "Emotional Awareness", @("well-being", "stress", "resilience", "support", "coping")),
    @("Yoga", "Breathing, movement, flexibility, focus, and mind-body awareness.", "Mindful Discipline", @("yoga", "posture", "breathing", "flexibility", "focus")),
    @("Meditation", "Attention, breathing, calmness, mindfulness, and reflection.", "Self-Regulation", @("meditation", "mindfulness", "attention", "calm", "reflection")),
    @("Healthy Habits", "Daily routines that support learning, energy, and confidence.", "Consistency", @("habit", "routine", "hydration", "screen time", "self-care")),
    @("Sleep Science", "Why sleep matters for memory, mood, growth, and health.", "Rest and Recovery", @("sleep", "circadian", "memory", "recovery", "routine"))
  )},
  @{ Category = "Arts & Creativity"; Topics = @(
    @("Painting", "Color, composition, observation, imagination, and visual storytelling.", "Visual Expression", @("painting", "color", "composition", "texture", "style")),
    @("Music", "Rhythm, melody, practice, listening, culture, and emotional expression.", "Listening and Practice", @("music", "rhythm", "melody", "harmony", "tempo")),
    @("Dance", "Movement, rhythm, discipline, culture, and storytelling through the body.", "Expressive Movement", @("dance", "movement", "rhythm", "choreography", "expression")),
    @("Photography", "Framing, light, perspective, moments, and visual evidence.", "Observation", @("photography", "frame", "light", "perspective", "composition")),
    @("Film", "Story, camera choices, editing, teamwork, and audience impact.", "Creative Collaboration", @("film", "scene", "editing", "script", "audience")),
    @("Theatre", "Performance, voice, character, teamwork, and live storytelling.", "Presence", @("theatre", "character", "stage", "dialogue", "rehearsal")),
    @("Design", "Solving problems through form, function, usability, and beauty.", "User Thinking", @("design", "prototype", "usability", "function", "aesthetic"))
  )},
  @{ Category = "Sports"; Topics = @(
    @("Olympic Games", "Global competition, excellence, respect, friendship, and preparation.", "Respectful Excellence", @("Olympics", "athlete", "excellence", "respect", "friendship")),
    @("World Cup", "Football/soccer, global fans, teamwork, pressure, and national pride.", "Team Identity", @("World Cup", "football", "teamwork", "fans", "strategy")),
    @("Great Athletes", "Training, discipline, setbacks, influence, and role models.", "Perseverance", @("athlete", "training", "discipline", "role model", "setback")),
    @("Teamwork", "Roles, trust, communication, shared goals, and group success.", "Shared Responsibility", @("teamwork", "role", "trust", "coordination", "goal")),
    @("Sportsmanship", "Fair play, respect, emotional control, and integrity.", "Integrity in Competition", @("sportsmanship", "fairness", "respect", "integrity", "grace")),
    @("Sports Science", "How training, body systems, data, and recovery affect performance.", "Evidence-Based Improvement", @("biomechanics", "recovery", "performance", "data", "conditioning"))
  )},
  @{ Category = "Digital Skills"; Topics = @(
    @("Coding", "Programming, logic, debugging, creativity, and building useful tools.", "Logical Thinking", @("coding", "algorithm", "debugging", "program", "logic")),
    @("Cybersecurity", "Protecting devices, accounts, data, and communities from digital risks.", "Digital Responsibility", @("cybersecurity", "password", "phishing", "privacy", "malware")),
    @("Internet Safety", "Safe sharing, trusted adults, scams, privacy, and respectful choices.", "Safe Judgment", @("internet safety", "privacy", "scam", "report", "trusted adult")),
    @("Digital Citizenship", "Respect, responsibility, credit, kindness, and participation online.", "Respect Online", @("citizenship", "respect", "source", "permission", "community")),
    @("Graphic Design", "Visual communication using layout, color, type, and audience thinking.", "Clear Visuals", @("layout", "typography", "contrast", "brand", "visual")),
    @("Video Editing", "Planning, sequencing, sound, pacing, and ethical storytelling.", "Story Structure", @("editing", "timeline", "clip", "audio", "sequence")),
    @("Responsible Use of AI", "Using AI tools honestly, safely, fairly, and with human judgment.", "Ethical Technology", @("AI", "bias", "prompt", "privacy", "verification"))
  )},
  @{ Category = "Communication"; Topics = @(
    @("Public Speaking", "Clear voice, structure, confidence, audience, and practice.", "Confident Delivery", @("speech", "audience", "voice", "structure", "practice")),
    @("Debate", "Arguments, evidence, listening, respectful disagreement, and rebuttal.", "Respectful Reasoning", @("debate", "argument", "evidence", "rebuttal", "resolution")),
    @("Persuasion", "Using reasons, stories, evidence, and trust to influence responsibly.", "Ethical Influence", @("persuasion", "reason", "evidence", "trust", "audience")),
    @("Interview Skills", "Preparation, listening, examples, confidence, and follow-up.", "Professional Presence", @("interview", "question", "example", "preparation", "follow-up")),
    @("Storytelling", "Beginning, conflict, emotion, lesson, and memorable delivery.", "Meaningful Narrative", @("storytelling", "plot", "character", "conflict", "lesson")),
    @("Body Language", "Posture, eye contact, gestures, facial expression, and presence.", "Nonverbal Awareness", @("posture", "gesture", "eye contact", "expression", "presence"))
  )},
  @{ Category = "STEM Challenges"; Topics = @(
    @("DIY Science", "Hands-on experiments, observation, variables, data, and explanation.", "Curiosity", @("experiment", "observation", "variable", "data", "hypothesis")),
    @("Engineering Challenges", "Designing, building, testing, improving, and explaining solutions.", "Design Thinking", @("engineering", "prototype", "constraint", "test", "iteration")),
    @("Math Puzzles", "Patterns, logic, strategy, persistence, and creative problem solving.", "Logical Persistence", @("puzzle", "pattern", "logic", "strategy", "proof")),
    @("Coding Challenges", "Solving problems with algorithms, debugging, and teamwork.", "Computational Thinking", @("algorithm", "debugging", "loop", "condition", "solution")),
    @("Robotics Projects", "Sensors, motors, coding, design, teamwork, and real-world tasks.", "Systems Thinking", @("robotics", "sensor", "motor", "automation", "system"))
  )},
  @{ Category = "Career Exploration"; Topics = @(
    @("Doctors", "Health care, diagnosis, teamwork, service, ethics, and lifelong learning.", "Service and Care", @("doctor", "patient", "diagnosis", "ethics", "care")),
    @("Engineers", "Designing systems, solving problems, testing, safety, and teamwork.", "Practical Problem Solving", @("engineer", "design", "system", "safety", "prototype")),
    @("Scientists", "Questions, evidence, experiments, data, and discovery.", "Evidence Seeking", @("scientist", "research", "evidence", "experiment", "discovery")),
    @("Artists", "Creative work, practice, identity, audience, and cultural expression.", "Creative Voice", @("artist", "studio", "portfolio", "expression", "audience")),
    @("Lawyers", "Rules, rights, evidence, argument, justice, and public service.", "Reasoned Advocacy", @("lawyer", "justice", "evidence", "rights", "argument")),
    @("Teachers", "Learning, patience, planning, communication, and student growth.", "Guiding Others", @("teacher", "lesson", "learning", "feedback", "patience")),
    @("Pilots", "Safety, navigation, communication, training, and calm decisions.", "Calm Responsibility", @("pilot", "navigation", "safety", "checklist", "communication")),
    @("Entrepreneurs", "Ideas, customers, risk, value, teams, and responsible innovation.", "Initiative", @("entrepreneur", "startup", "customer", "risk", "value")),
    @("AI Professionals", "Building, testing, evaluating, and using AI systems responsibly.", "Responsible Innovation", @("AI", "model", "data", "bias", "evaluation")),
    @("Environmental Scientists", "Studying ecosystems, climate, pollution, conservation, and solutions.", "Stewardship", @("environment", "ecosystem", "climate", "pollution", "conservation"))
  )}
)

foreach ($spec in $globalTopicSpecs) {
  foreach ($topic in $spec.Topics) {
    $items += New-GlobalTopicItem $spec.Category $topic[0] $topic[1] $topic[2] $topic[3]
  }
}

function Page-Head([string]$title, [string]$description, [string]$pathPrefix) {
  $safeTitle = Escape-Html $title
  $safeDescription = Escape-Html $description
@"
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>$safeTitle | Yuva Club</title>
  <meta name="description" content="$safeDescription">
  <meta property="og:title" content="$safeTitle | Yuva Club">
  <meta property="og:description" content="$safeDescription">
  <meta property="og:image" content="https://yuvaclub.karmabro.com/assets/logo.png">
  <meta property="og:url" content="https://yuvaclub.karmabro.com/">
  <meta property="og:type" content="website">
  <link rel="icon" href="${pathPrefix}assets/logo.png" type="image/png">
  <link rel="apple-touch-icon" href="${pathPrefix}assets/app-icon-180.png">
  <link rel="manifest" href="${pathPrefix}manifest.webmanifest">
  <meta name="theme-color" content="#062856">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-title" content="YUVA Club">
  <meta name="apple-mobile-web-app-status-bar-style" content="default">
  <link rel="stylesheet" href="${pathPrefix}assets/site.css?v=20260614-large-photos">
  <script src="${pathPrefix}assets/app.js" defer></script>
</head>
<body>
"@
}

function Site-Header([string]$pathPrefix) {
@"
  <header class="site-header">
    <a class="brand" href="${pathPrefix}index.html" aria-label="Yuva Club home">
      <img src="${pathPrefix}assets/logo.png" alt="Yuva Club logo" width="78" height="78">
      <span>Yuva Club</span>
    </a>
    <nav class="nav" aria-label="Main navigation">
      <a href="${pathPrefix}index.html">Home</a>
      <a href="${pathPrefix}programs.html">Programs</a>
      <a href="${pathPrefix}challenges.html">Challenges</a>
      <a href="${pathPrefix}curriculum.html">Topics</a>
      <a href="${pathPrefix}resources.html">Resources</a>
      <a href="${pathPrefix}stories.html">Stories</a>
      <a href="${pathPrefix}leaderboard.php">Leaderboard</a>
      <a href="${pathPrefix}app.html">App</a>
      <a href="${pathPrefix}safety.html">Safety</a>
      <a href="${pathPrefix}registration.php">Register</a>
      <a href="${pathPrefix}portal-login.php">Student Portal</a>
      <a href="${pathPrefix}parent-login.php">Parent</a>
      <a href="${pathPrefix}admin-login.php">Admin</a>
    </nav>
  </header>
"@
}

function Site-Footer([string]$pathPrefix) {
@"
  <footer class="site-footer">
    <div>
      <strong>Yuva Club</strong>
      <p>A youth leadership development platform that empowers students through research, presentations, discussion, critical thinking, and peer learning.</p>
      <p><a href="https://www.karmabro.com/">www.karmabro.com</a></p>
      <p>&copy; 2026 KarmaBro. All rights reserved.</p>
    </div>
  </footer>
</body>
</html>
"@
}

function Story-Card($item, [string]$pathPrefix) {
  $cat = Escape-Html $item.category
  $title = Escape-Html $item.title
  $subtitle = Escape-Html $item.subtitle
  $skill = Escape-Html $item.skill
  $slug = Escape-Html $item.slug
  $image = Topic-Image $item
  $imageHtml = ""
  if ($image) {
    $imageHtml = "<img class=`"story-card-image`" src=`"${pathPrefix}assets/topics/$image`" alt=`"$title`">"
  }
@"
        <a class="story-card" href="${pathPrefix}pages/$slug.html">
$imageHtml
          <span class="card-kicker">$cat</span>
          <strong>$title</strong>
          <span>$subtitle</span>
          <em>$skill</em>
        </a>
"@
}

function Category-Description([string]$category) {
  switch ($category) {
    "Leadership & Inspiration" { "Explore great leaders, young changemakers, Nobel Prize winners, humanitarian leaders, social reformers, and women who changed the world." }
    "Science & Technology" { "Explore space, artificial intelligence, robotics, medical discoveries, renewable energy, future technologies, famous scientists, and great inventors." }
    "Business & Entrepreneurship" { "Study entrepreneurs, startup stories, brands that changed the world, financial literacy, marketing, and innovation." }
    "History & Civilization" { "Understand ancient civilizations, world history, historical events, great empires, archaeology, and ancient wonders." }
    "Geography & Cultures" { "Present countries, world cultures, languages, traditions, festivals, and UNESCO World Heritage Sites." }
    "Architecture & Engineering" { "Study famous buildings, bridges, skyscrapers, ancient architecture, modern engineering marvels, and smart cities." }
    "Environment" { "Explore climate change, wildlife, oceans, national parks, sustainability, recycling, and biodiversity." }
    "Health & Wellness" { "Discuss nutrition, exercise, mental well-being, yoga, meditation, healthy habits, and sleep science." }
    "Books & Literature" { "Present famous authors, classic books, children's literature, poetry, book reviews, and storytelling." }
    "Arts & Creativity" { "Explore painting, music, dance, photography, film, theatre, and design." }
    "Sports" { "Present the Olympic Games, World Cup, great athletes, teamwork, sportsmanship, and sports science." }
    "Digital Skills" { "Practice coding, cybersecurity, internet safety, digital citizenship, graphic design, video editing, and responsible AI use." }
    "Communication" { "Practice public speaking, debate, persuasion, interview skills, storytelling, and body language." }
    "Character Development" { "Build kindness, integrity, leadership, teamwork, time management, goal setting, emotional intelligence, and problem solving." }
    "Community & Service" { "Present everyday service leaders such as doctors, teachers, firefighters, police, volunteers, and nonprofit leaders." }
    "STEM Challenges" { "Explore DIY science, engineering challenges, math puzzles, coding challenges, and robotics projects." }
    "Career Exploration" { "Research careers such as doctors, engineers, scientists, artists, lawyers, teachers, pilots, entrepreneurs, AI professionals, and environmental scientists." }
    default { "Reading pages with discussion questions and leadership prompts." }
  }
}

function Category-Suggestions([string]$category) {
  switch ($category) {
    "Leadership & Inspiration" { @("Nelson Mandela", "A.P.J. Abdul Kalam", "Abraham Lincoln", "Malala Yousafzai", "Wangari Maathai", "Mother Teresa") }
    "Science & Technology" { @("Marie Curie", "Albert Einstein", "Ada Lovelace", "Katherine Johnson", "Tu Youyou", "Sunita Williams", "Artificial Intelligence") }
    "Business & Entrepreneurship" { @("Steve Jobs", "Elon Musk", "Sundar Pichai", "Walt Disney", "Startup Stories", "Brands That Changed the World") }
    "History & Civilization" { @("Ancient Egypt", "Ancient Greece", "Ancient Rome", "Maya Civilization", "World History", "Ancient Wonders") }
    "Geography & Cultures" { @("Countries of the World", "World Cultures", "Languages", "Traditions", "Festivals", "UNESCO World Heritage Sites") }
    "Architecture & Engineering" { @("Great Wall of China", "Taj Mahal", "Pyramids of Giza", "Machu Picchu", "Bridges", "Skyscrapers", "Smart Cities") }
    "Environment" { @("Climate Change", "Wildlife", "Oceans", "National Parks", "Sustainability", "Recycling", "Biodiversity") }
    "Health & Wellness" { @("Nutrition", "Exercise", "Mental Well-being", "Yoga", "Meditation", "Healthy Habits", "Sleep Science") }
    "Books & Literature" { @("Famous Authors", "Classic Books", "Children's Literature", "Poetry", "Book Reviews", "Storytelling") }
    "Arts & Creativity" { @("Painting", "Music", "Dance", "Photography", "Film", "Theatre", "Design") }
    "Sports" { @("Olympic Games", "World Cup", "Great Athletes", "Teamwork", "Sportsmanship", "Sports Science") }
    "Digital Skills" { @("Coding", "Cybersecurity", "Internet Safety", "Digital Citizenship", "Graphic Design", "Video Editing", "Responsible Use of AI") }
    "Communication" { @("Public Speaking", "Debate", "Persuasion", "Interview Skills", "Storytelling", "Body Language") }
    "Character Development" { @("Kindness", "Integrity", "Leadership", "Teamwork", "Time Management", "Goal Setting", "Emotional Intelligence", "Problem Solving") }
    "Community & Service" { @("Volunteering", "Community Projects", "Charity", "Civic Responsibility", "Environmental Action") }
    "STEM Challenges" { @("DIY Science", "Engineering Challenges", "Math Puzzles", "Coding Challenges", "Robotics Projects") }
    "Career Exploration" { @("Doctors", "Engineers", "Scientists", "Artists", "Lawyers", "Teachers", "Pilots", "Entrepreneurs", "AI Professionals", "Environmental Scientists") }
    default { @("Student-selected topic") }
  }
}

function Topic-Image($item) {
  switch ($item.slug) {
    "vivekananda-childhood" { "swami-vivekananda.png" }
    "vivekananda-chicago" { "swami-vivekananda.png" }
    "vivekananda-service" { "swami-vivekananda.png" }
    "kalam-childhood" { "apj-abdul-kalam.png" }
    "kalam-scientist" { "apj-abdul-kalam.png" }
    "kalam-president" { "apj-abdul-kalam.png" }
    "mahatma-gandhi" { "mahatma-gandhi.png" }
    "chanakya" { "chanakya.png" }
    "aryabhata" { "aryabhata.png" }
    "rani-lakshmibai" { "rani-lakshmibai.png" }
    "subhas-chandra-bose" { "subhas-chandra-bose.png" }
    "sardar-patel" { "sardar-patel.png" }
    "kalpana-chawla" { "kalpana-chawla.png" }

    "bhishma-vow" { "bhishma.png" }
    "pandavas-childhood" { "yudhishthira.png" }
    "arjuna-focus" { "arjuna.png" }
    "draupadi-courage" { "draupadi.png" }
    "krishna-arjuna-gita" { "krishna.png" }

    "rama-exile" { "rama.png" }
    "sita-strength" { "sita.png" }
    "hanuman-leap" { "hanuman.png" }
    "bharata-sandals" { "bharata.png" }

    "dharma" { "responsibility.png" }
    "namaste" { "respect.png" }
    "diwali" { "service.png" }
    "holi" { "compassion.png" }
    "guru" { "respect.png" }

    "panchatantra-mongoose" { "responsibility.png" }
    "panchatantra-lion-hare" { "courage.png" }
    "panchatantra-monkey-crocodile" { "honesty.png" }
    "panchatantra-tortoise-geese" { "perseverance.png" }

    "steve-jobs" { "steve-jobs.png" }
    "elon-musk" { "elon-musk.png" }
    "bill-gates" { "bill-gates.png" }
    "sundar-pichai" { "sundar-pichai.png" }
    "satya-nadella" { "satya-nadella.png" }
    "jensen-huang" { "jensen-huang.png" }
    "larry-page-sergey-brin" { "larry-page-sergey-brin.png" }
    "narayana-murthy" { "narayana-murthy.png" }
    "ratan-tata" { "ratan-tata.png" }
    default { "" }
  }
}

function Topic-Context($item) {
  switch ($item.category) {
    "Mahabharata" { "The Mahabharata often shows people making difficult choices under pressure. As you read this story, pay attention not only to the events, but also to the motives, relationships, and consequences behind each decision." }
    "Ramayana" { "The Ramayana invites readers to think about character, duty, loyalty, and courage. As you read this story, notice how the characters respond when life becomes difficult or unfair." }
    "Swami Vivekananda" { "Swami Vivekananda's life connects spiritual strength with service, confidence, and youth leadership. As you read, look for the habits that made his words powerful and his actions meaningful." }
    "Dr. A.P.J. Abdul Kalam" { "Dr. A.P.J. Abdul Kalam's life connects dreams with discipline, science, humility, and service. As you read, notice how effort and purpose work together." }
    "Panchatantra" { "Panchatantra stories use simple situations to teach complex thinking. As you read, look for the moment when a character must pause, judge wisely, and choose a path." }
    "Great Indians" { "This profile is part of a larger journey through Indian history, knowledge, courage, and service. As you read, notice how one person's choices can inspire many generations." }
    "Hindu Traditions" { "Traditions become meaningful when students connect them to daily life. As you read, think about how this value or practice can shape respect, family life, community, and self-discipline." }
    "Innovators, Entrepreneurs & Changemakers" { "This profile connects leadership with the modern world of ideas, companies, invention, science, ethics, and social impact. As you read, notice how curiosity becomes action and how action can affect many people." }
    "Everyday Leaders" { "This session turns attention from famous leaders to real people students know. As you read, think about how leadership can appear in ordinary acts of courage, kindness, responsibility, service, and encouragement." }
    "Environment & Nature" { "This topic connects science with responsibility. As you read, notice how nature, people, systems, and choices are connected, and think about what responsible leadership looks like when the whole community shares the same planet." }
    "Community & Service" { "This topic helps students recognize service leadership in real life. As you read, notice the skills, sacrifices, teamwork, and trust required when people serve a community." }
    "Life Skills" { "This topic is about practical leadership habits. As you read, look for one skill students can practice immediately in school, family life, Yuva Club, or future careers." }
    "Great Monuments & Architecture" { "This topic connects history, engineering, art, culture, and human ambition. As you read, notice how design choices reveal what a society valued and what kinds of organization were needed to build something lasting." }
    "Space & Technology" { "This topic connects curiosity with technical skill and responsibility. As you read, notice how missions, machines, data, and teamwork can expand knowledge and solve real problems." }
    default { "As you read, pay attention to the choices, challenges, and values in the story. These details will help you prepare for a meaningful group discussion." }
  }
}

function Expand-Reading($item) {
  $paragraphs = @($item.reading)
  $currentText = ($paragraphs -join " ")
  $wordCount = ([regex]::Matches($currentText, "\b[\w']+\b")).Count
  if ($wordCount -ge 300) { return $paragraphs }

  $title = $item.title
  $skill = $item.skill
  $activity = $item.activity
  $context = Topic-Context $item

  $paragraphs += $context
  $paragraphs += "For teenagers, the most important part of $title is not memorizing names or dates. The deeper goal is to ask what kind of person the story is training us to become. The leadership skill for this page is $skill. That means students should look for examples of responsibility, self-control, courage, humility, or clear thinking, and then connect those examples to school, friendships, family, and community life."
  $paragraphs += "A strong presenter should explain the background, the turning point, and the lesson. The background tells the group what is happening. The turning point shows the choice or challenge. The lesson explains why the story still matters today. This structure helps the presenter speak clearly and helps listeners prepare thoughtful comments."
  $paragraphs += "During discussion, avoid giving only one-word answers. Support your ideas with a reason from the reading and an example from real life. You may agree or disagree respectfully, but the goal is to think deeply together. When students listen carefully, ask better questions, and build on each other's ideas, the club becomes more than a reading group. It becomes a place to practice leadership."
  $paragraphs += "After the session, try the practical takeaway: $activity This turns the reading into action. The best lessons are not only remembered; they are practiced in small choices during the week."
  return $paragraphs
}

function Optional-Challenge($item) {
  switch ($item.type) {
    "Person" { "Prepare a one-minute mini presentation explaining one challenge this leader faced, one value they demonstrated, and one habit students can practice from their life." }
    "People" { "Prepare a one-minute mini presentation explaining how teamwork helped these people succeed and what your group can learn from their process." }
    "Festival" { "Ask a family member how this festival is celebrated in your home or community, then share one tradition and one value behind it." }
    "Tradition" { "Observe this value or practice during the week and write a short reflection on how it can make daily life more respectful or meaningful." }
    "Student Presentation" { "After your presentation, write a thank-you note or personally thank the person you chose as your hero." }
    default { "Write a short reflection or prepare a one-minute talk about how the leadership lesson appears in your own school, family, or community life." }
  }
}

function Topic-Matters($item) {
  $title = $item.title
  $skill = $item.skill
  switch ($item.category) {
    "Mahabharata" {
      "This topic helps students explore how people make choices when family, duty, pride, loyalty, and fairness all pull in different directions. The goal is not only to remember the story of $title, but to discuss how $skill can guide real decisions in school, friendships, and family life."
    }
    "Ramayana" {
      "This topic helps students think about character under pressure. $title gives the group a chance to discuss duty, respect, courage, and self-control in situations where the right choice may not be the easiest choice."
    }
    "Swami Vivekananda" {
      "This topic connects Indian spiritual wisdom with youth confidence, service, and public speaking. Students should look for how ideas become powerful when they are spoken clearly and lived with discipline."
    }
    "Dr. A.P.J. Abdul Kalam" {
      "This topic connects dreams with effort, science, humility, and service. Students can discuss how a leader builds success through habits, learning, teamwork, and a purpose larger than personal achievement."
    }
    "Panchatantra" {
      "This topic uses a simple story to practice deeper thinking. Students should discuss what the characters noticed, what they missed, and how better judgment could change the outcome."
    }
    "Great Indians" {
      "This topic introduces a person whose life can help students think about knowledge, courage, service, and identity. Students should connect the leader's choices to the kind of person they want to become."
    }
    "Hindu Traditions" {
      "This topic helps students connect culture with daily practice. The discussion should focus on what the tradition means, why families preserve it, and how it can shape respectful modern life."
    }
    "Innovators, Entrepreneurs & Changemakers" {
      "This topic helps students understand how ideas become products, companies, discoveries, movements, or social impact. Students should discuss not only success, but also risk, ethics, communication, persistence, and responsibility."
    }
    "Everyday Leaders" {
      "This topic helps students notice leadership in real life. The discussion should focus on everyday examples of responsibility, kindness, courage, service, and the people who quietly influence us."
    }
    "Environment & Nature" {
      "This topic helps students connect environmental knowledge with leadership. Students should discuss how $title affects people, communities, and ecosystems, and how $skill can guide responsible action."
    }
    "Community & Service" {
      "This topic helps students understand that service is a serious form of leadership. Students should discuss how $title depends on trust, preparation, communication, and responsibility."
    }
    "Life Skills" {
      "This topic helps students practice a skill they can use immediately. Students should connect $title to school, presentations, teamwork, family life, and long-term leadership growth."
    }
    "Great Monuments & Architecture" {
      "This topic helps students study $title as more than a famous landmark. Students should connect history, engineering, culture, art, and leadership, then discuss what human achievement can teach us about responsibility."
    }
    "Space & Technology" {
      "This topic helps students understand how science and technology move from imagination to real systems. Students should discuss how $title depends on curiosity, teamwork, testing, ethics, and purposeful innovation."
    }
    default {
      "This topic gives students a chance to connect a story or life example to practical leadership. The goal is to discuss, question, listen, and apply the lesson."
    }
  }
}

function Open-Discussion-Question([string]$question) {
  $question = $question.Trim()
  if ($question -match '^(Can|Is|Are|Do|Does|Did|Would|Should)\b') {
    return "$question Why or why not? Share an example from the reading or from real life."
  }
  if ($question -match '^(What|Why|How|Which|Who|When|Where)\b') {
    return "$question Explain your thinking with evidence or an example."
  }
  return "$question Discuss your answer with a reason and an example."
}

function Expand-Questions($item) {
  $questions = @($item.questions)
  $additions = switch ($item.category) {
    "Mahabharata" {
      @(
        "Which character showed leadership in this story, and how?",
        "What choice in the story had the biggest consequence?",
        "How does this story connect to friendships, school, or family life?"
      )
    }
    "Ramayana" {
      @(
        "Which character showed strong character in this story?",
        "What does this story teach about duty or integrity?",
        "How can this lesson help a student make better choices?"
      )
    }
    "Swami Vivekananda" {
      @(
        "What quality made Vivekananda inspiring to young people?",
        "How did his words or actions show confidence with humility?",
        "How can students use this lesson in public speaking or service?"
      )
    }
    "Dr. A.P.J. Abdul Kalam" {
      @(
        "How did Kalam connect dreams with hard work?",
        "What does this story teach about learning from failure?",
        "How can science, service, and leadership work together?"
      )
    }
    "Panchatantra" {
      @(
        "What mistake or wise choice changed the story?",
        "What would you have done differently in this situation?",
        "How does this moral apply to modern student life?"
      )
    }
    "Great Indians" {
      @(
        "What challenge did this person face?",
        "Which value from this life is most useful for students?",
        "How did this person's work help others?"
      )
    }
    "Hindu Traditions" {
      @(
        "How does this tradition connect to values in daily life?",
        "How can families explain this idea to younger children?",
        "What is one respectful way to practice or remember this tradition?"
      )
    }
    "Innovators, Entrepreneurs & Changemakers" {
      @(
        "What problem or opportunity shaped this innovator or changemaker?",
        "How did an idea become action, invention, company, or social impact?",
        "What ethical responsibility comes with this kind of leadership?"
      )
    }
    "Leadership Around Us" {
      @(
        "What makes an everyday person a real leader?",
        "How can we show gratitude to people who guide us?",
        "What leadership quality do you want to practice this month?"
      )
    }
    default {
      @(
        "What value is most important in this reading?",
        "How can students practice this lesson?",
        "What question would you ask the main character or leader?"
      )
    }
  }

  foreach ($question in $additions) {
    if ($questions.Count -ge 5) { break }
    if (-not ($questions -contains $question)) {
      $questions += $question
    }
  }

  return $questions | Select-Object -First 5
}

$css = @'
:root {
  --ink: #1d2433;
  --muted: #657084;
  --paper: #fffaf0;
  --surface: #ffffff;
  --line: #e7ddca;
  --saffron: #f59e0b;
  --marigold: #facc15;
  --lotus: #d946ef;
  --leaf: #16a34a;
  --teal: #0f766e;
  --blue: #2563eb;
  --shadow: 0 18px 55px rgba(29, 36, 51, .12);
}

* { box-sizing: border-box; }
html { scroll-behavior: smooth; }
body {
  margin: 0;
  color: var(--ink);
  background:
    radial-gradient(circle at 10% 0%, rgba(250, 204, 21, .24), transparent 32rem),
    radial-gradient(circle at 90% 12%, rgba(22, 163, 74, .14), transparent 28rem),
    linear-gradient(180deg, #fff8eb 0%, #fffdf8 46%, #f7fbff 100%);
  font-family: Arial, Helvetica, sans-serif;
  line-height: 1.6;
}

a { color: inherit; }

.site-header {
  position: sticky;
  top: 0;
  z-index: 10;
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 20px;
  padding: 14px clamp(18px, 4vw, 58px);
  background: rgba(255, 250, 240, .92);
  border-bottom: 1px solid rgba(231, 221, 202, .82);
  backdrop-filter: blur(14px);
}

.brand {
  display: inline-flex;
  align-items: center;
  gap: 10px;
  font-weight: 800;
  text-decoration: none;
  letter-spacing: 0;
}

.brand img {
  flex: 0 0 auto;
  width: 78px;
  height: 78px;
  border-radius: 50%;
  object-fit: cover;
}

.nav {
  display: flex;
  align-items: center;
  gap: 6px;
  flex-wrap: wrap;
  justify-content: flex-end;
}

.nav a {
  min-height: 36px;
  display: inline-flex;
  align-items: center;
  padding: 7px 11px;
  color: #3f4858;
  text-decoration: none;
  font-size: 14px;
  font-weight: 700;
}

.nav a:hover { color: #0f766e; }

.hero {
  min-height: 76vh;
  display: grid;
  grid-template-columns: minmax(0, 1.02fr) minmax(280px, .98fr);
  align-items: center;
  gap: clamp(28px, 5vw, 70px);
  padding: clamp(34px, 6vw, 86px) clamp(18px, 5vw, 72px) 42px;
}

.eyebrow {
  color: #8a4c00;
  font-weight: 800;
  text-transform: uppercase;
  font-size: 13px;
  letter-spacing: .08em;
}

h1, h2, h3 {
  line-height: 1.1;
  margin: 0;
  letter-spacing: 0;
}

h1 {
  max-width: 850px;
  font-size: clamp(44px, 8vw, 92px);
}

h2 { font-size: clamp(28px, 4vw, 48px); }
h3 { font-size: 22px; }

.hero p {
  max-width: 720px;
  font-size: clamp(18px, 2.2vw, 23px);
  color: #414b5f;
}

.hero-actions, .button-row {
  display: flex;
  gap: 12px;
  flex-wrap: wrap;
  margin-top: 26px;
}

.button {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  min-height: 44px;
  padding: 10px 15px;
  border-radius: 8px;
  border: 1px solid #d8cab4;
  background: #fff;
  text-decoration: none;
  font-weight: 800;
  box-shadow: 0 8px 22px rgba(29, 36, 51, .08);
}

.button.primary {
  background: #14332f;
  color: #fff;
  border-color: #14332f;
}

.hero-art {
  min-height: 0;
  aspect-ratio: 1696 / 928;
  border-radius: 8px;
  background: #fff url("ycfront.png") center / contain no-repeat;
  box-shadow: var(--shadow);
  position: relative;
  overflow: hidden;
}

.topics-poster {
  width: min(100%, 1320px);
  display: block;
  margin: 22px auto 0;
  border-radius: 8px;
  border: 1px solid var(--line);
  box-shadow: var(--shadow);
  background: #fff;
}

.band {
  padding: clamp(42px, 6vw, 76px) clamp(18px, 5vw, 72px);
}

.band.alt { background: rgba(255, 255, 255, .55); }

.section-head {
  max-width: 860px;
  margin-bottom: 26px;
}

.section-head p {
  color: var(--muted);
  font-size: 18px;
}

.grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 18px;
}

.two-grid {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
  gap: 20px;
}

.feature, .story-card, .month-card, .resource-card, .lesson-panel, .reading-panel {
  background: rgba(255, 255, 255, .86);
  border: 1px solid var(--line);
  border-radius: 8px;
  box-shadow: 0 12px 32px rgba(29, 36, 51, .08);
}

.feature, .month-card, .resource-card, .lesson-panel { padding: 20px; }

.feature strong, .month-card strong, .resource-card strong {
  display: block;
  font-size: 20px;
  margin-bottom: 8px;
}

.feature p, .month-card p, .resource-card p { color: var(--muted); margin: 0; }

.story-list {
  display: grid;
  grid-template-columns: repeat(4, minmax(0, 1fr));
  gap: 16px;
}

.story-card {
  min-height: 250px;
  padding: 18px;
  display: flex;
  flex-direction: column;
  gap: 10px;
  text-decoration: none;
}

.story-card-image {
  width: 100%;
  height: 142px;
  object-fit: cover;
  border-radius: 7px;
  border: 1px solid rgba(231, 221, 202, .9);
  background: #f8efe0;
}

.story-card:hover {
  transform: translateY(-2px);
  border-color: rgba(15, 118, 110, .42);
}

.story-card strong { font-size: 20px; line-height: 1.18; }
.story-card span { color: var(--muted); }
.story-card em {
  margin-top: auto;
  color: #0f766e;
  font-style: normal;
  font-weight: 800;
}

.card-kicker {
  color: #9a5a00 !important;
  font-weight: 800;
  font-size: 12px;
  text-transform: uppercase;
}

.story-hero {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(360px, 560px);
  gap: clamp(24px, 5vw, 56px);
  align-items: center;
  padding: clamp(36px, 6vw, 78px) clamp(18px, 5vw, 72px);
}

.story-badge {
  display: inline-flex;
  margin-bottom: 12px;
  padding: 6px 9px;
  border-radius: 6px;
  background: #fff1c8;
  color: #7c4300;
  font-weight: 800;
  font-size: 13px;
}

.story-hero h1 { font-size: clamp(38px, 7vw, 76px); }
.story-hero p { font-size: 20px; color: #4c5668; max-width: 690px; }

.story-symbol {
  width: min(100%, 560px);
  aspect-ratio: 4 / 3;
  display: grid;
  place-items: center;
  border-radius: 8px;
  background: linear-gradient(135deg, #fff, #fff0ce);
  border: 1px solid var(--line);
  box-shadow: var(--shadow);
}

.story-symbol span {
  width: 68%;
  aspect-ratio: 1;
  display: grid;
  place-items: center;
  border-radius: 50%;
  color: white;
  background: conic-gradient(from 120deg, var(--teal), var(--saffron), var(--lotus), var(--blue), var(--teal));
  font-size: clamp(52px, 9vw, 104px);
  font-weight: 900;
}

.story-photo {
  overflow: hidden;
  background: #fff;
  justify-self: center;
  padding: 0;
}

.story-photo img {
  width: 100%;
  height: 100%;
  object-fit: cover !important;
  display: block;
}

.story-layout {
  display: grid;
  grid-template-columns: minmax(220px, .32fr) minmax(0, 1fr);
  gap: 22px;
  padding: 0 clamp(18px, 5vw, 72px) 72px;
}

.lesson-panel {
  align-self: start;
  position: sticky;
  top: 86px;
}

.meta-list {
  display: grid;
  gap: 12px;
  margin-top: 16px;
}

.meta-list div {
  border-top: 1px solid var(--line);
  padding-top: 12px;
}

.meta-list span {
  display: block;
  color: var(--muted);
  font-size: 13px;
  font-weight: 800;
  text-transform: uppercase;
}

.meta-list strong { font-size: 18px; }

.reading-panel {
  padding: clamp(22px, 4vw, 42px);
}

.reading-panel p {
  font-size: 19px;
  color: #30394a;
}

.reading-panel h2 {
  margin-top: 30px;
  font-size: 28px;
}

.pill-list, .question-list {
  display: flex;
  gap: 10px;
  flex-wrap: wrap;
  padding: 0;
  margin: 14px 0 0;
  list-style: none;
}

.pill-list li {
  padding: 7px 10px;
  border-radius: 999px;
  background: #eef8f5;
  color: #0f766e;
  font-weight: 800;
  font-size: 14px;
}

.question-list {
  display: grid;
  gap: 10px;
}

.question-list li {
  padding: 13px 14px;
  border-radius: 8px;
  background: #fff8e7;
  border: 1px solid #f0dfbd;
}

.challenge-box {
  margin-top: 18px;
  padding: 18px;
  border-radius: 8px;
  border: 1px solid rgba(15, 118, 110, .24);
  background: #eef8f5;
}

.challenge-box h2 {
  margin-top: 0;
  font-size: 24px;
}

.challenge-box p { margin-bottom: 0; }

.student-question-box {
  margin-top: 18px;
  padding: 18px;
  border-radius: 8px;
  border: 1px solid #f0dfbd;
  background: #fff8e7;
}

.student-question-box h2 {
  margin-top: 0;
  font-size: 24px;
}

.student-question-box label {
  display: block;
  margin-bottom: 8px;
  font-weight: 800;
}

.student-question-box textarea {
  width: 100%;
  min-height: 110px;
  border: 1px solid #d8cab4;
  border-radius: 8px;
  padding: 11px 12px;
  font: inherit;
  color: var(--ink);
  background: #fff;
  resize: vertical;
}

.form-shell {
  max-width: 980px;
  margin: 0 auto;
}

.form-card {
  display: grid;
  gap: 18px;
  padding: clamp(20px, 4vw, 34px);
  background: rgba(255, 255, 255, .9);
  border: 1px solid var(--line);
  border-radius: 8px;
  box-shadow: var(--shadow);
}

.form-card h2 {
  margin: 10px 0 0;
  padding-top: 16px;
  border-top: 1px solid var(--line);
  font-size: clamp(22px, 3vw, 30px);
}

.form-card h2:first-of-type {
  margin-top: 0;
  padding-top: 0;
  border-top: 0;
}

.meeting-list {
  display: grid;
  gap: 16px;
}

.meeting-card {
  padding: 16px;
  border: 1px solid var(--line);
  border-radius: 8px;
  background: rgba(255, 255, 255, 0.74);
}

.meeting-card h3 {
  margin-top: 0;
}

.field-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 16px;
}

.field {
  display: grid;
  gap: 7px;
}

.field label,
.choice-group legend {
  font-weight: 800;
  color: #273143;
}

.field input,
.field select,
.field textarea {
  width: 100%;
  min-height: 44px;
  border: 1px solid #d8cab4;
  border-radius: 8px;
  padding: 10px 12px;
  font: inherit;
  color: var(--ink);
  background: #fff;
}

.field textarea {
  min-height: 120px;
  resize: vertical;
}

.choice-group {
  border: 1px solid var(--line);
  border-radius: 8px;
  padding: 16px;
  margin: 0;
  background: #fffdf8;
}

.choice-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: 10px;
  margin-top: 12px;
}

.choice,
.choice-grid label,
.choice-stack label {
  display: flex;
  align-items: flex-start;
  gap: 9px;
  padding: 10px;
  border-radius: 8px;
  background: #fff;
  border: 1px solid #eadcc6;
  font-weight: 700;
}

.choice-stack {
  display: grid;
  gap: 10px;
  margin-top: 12px;
}

.choice input,
.choice-grid input,
.choice-stack input {
  margin-top: 4px;
}

.preference-grid {
  display: grid;
  gap: 14px;
  margin-top: 12px;
}

.preference-row {
  display: grid;
  grid-template-columns: minmax(0, 1fr) minmax(160px, .55fr);
  gap: 14px;
  padding: 14px;
  border: 1px solid #eadcc6;
  border-radius: 8px;
  background: #fff;
}

.form-note {
  color: var(--muted);
  margin: 0;
}

.form-status {
  padding: 14px 16px;
  border-radius: 8px;
  margin-bottom: 18px;
  font-weight: 800;
}

.form-status.success {
  color: #14532d;
  background: #dcfce7;
  border: 1px solid #86efac;
}

.form-status.error {
  color: #7f1d1d;
  background: #fee2e2;
  border: 1px solid #fca5a5;
}

.portal-narrow {
  max-width: 720px;
}

.portal-stat-grid {
  display: grid;
  grid-template-columns: repeat(5, minmax(0, 1fr));
  gap: 16px;
}

.portal-module-grid,
.portal-profile-grid,
.three-grid {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 16px;
}

.portal-profile-grid {
  grid-template-columns: minmax(0, 1fr) minmax(0, 1fr);
}

.portal-module {
  color: inherit;
  text-decoration: none;
}

.hub-card {
  align-content: start;
}

.zoom-scheduler-frame {
  width: 100%;
  max-width: 750px;
  overflow: hidden;
  border: 1px solid var(--line);
  border-radius: 8px;
  background: #fff;
  margin: 0 auto;
}

.zoom-scheduler-frame iframe {
  display: block;
  width: 100%;
  height: 560px;
}

.compact-scheduler-frame {
  margin-top: 14px;
}

.compact-scheduler-frame iframe {
  height: 460px;
}

.zoom-meeting-frame {
  width: 100%;
  margin-top: 14px;
  overflow: hidden;
  border: 1px solid var(--line);
  border-radius: 8px;
  background: #fff;
}

.zoom-meeting-frame iframe {
  display: block;
  width: 100%;
  height: 420px;
}

.compact-choice-grid {
  max-height: 280px;
  overflow: auto;
}

.compact-choice-grid label span {
  display: block;
  color: var(--muted);
  font-size: 13px;
  margin-top: 2px;
}

.badge-list {
  display: flex;
  flex-wrap: wrap;
  gap: 10px;
}

.badge-list span {
  padding: 8px 10px;
  border-radius: 999px;
  background: #eef8f5;
  border: 1px solid rgba(15, 118, 110, .24);
  color: #0f5f59;
  font-weight: 800;
}

.challenge-path {
  display: flex;
  flex-wrap: wrap;
  gap: .7rem;
  margin: 1.4rem 0;
}

.challenge-path span {
  border: 1px solid rgba(6, 40, 86, .16);
  border-radius: 999px;
  background: rgba(255, 255, 255, .82);
  color: var(--muted);
  font-weight: 800;
  padding: .55rem .85rem;
}

.challenge-path span.active {
  background: var(--navy);
  color: #fff;
  border-color: var(--navy);
  box-shadow: 0 10px 22px rgba(6, 40, 86, .16);
}

.rubric-grid {
  display: grid;
  grid-template-columns: repeat(2, minmax(0, 1fr));
  gap: .7rem;
  margin-top: 1rem;
}

.rubric-grid div {
  border: 1px solid rgba(6, 40, 86, .12);
  border-radius: 8px;
  background: rgba(255, 255, 255, .76);
  padding: .75rem;
}

.rubric-grid strong,
.rubric-grid span {
  display: block;
}

.rubric-grid span {
  color: var(--muted);
  margin-top: .25rem;
}

.ai-review-box {
  margin-top: 12px;
  padding: 12px;
  border: 1px solid rgba(6, 40, 86, .18);
  border-radius: 8px;
  background: #f7fbff;
}

.portal-table-wrap {
  overflow-x: auto;
  background: rgba(255,255,255,.92);
  border: 1px solid var(--line);
  border-radius: 8px;
  box-shadow: var(--shadow);
}

.portal-table {
  width: 100%;
  min-width: 1120px;
  border-collapse: collapse;
}

.compact-table {
  min-width: 760px;
}

.portal-table th,
.portal-table td {
  padding: 14px;
  border-bottom: 1px solid var(--line);
  vertical-align: top;
  text-align: left;
}

.portal-table th {
  background: #fff8e7;
  color: #273143;
}

.portal-table .field {
  margin-bottom: 10px;
}

.portal-table textarea {
  min-height: 72px;
}

.certificate-page {
  background: #f7f2e7;
}

.certificate-shell {
  min-height: 100vh;
  display: grid;
  place-items: center;
  padding: 30px;
}

.certificate-card {
  width: min(980px, 100%);
  min-height: 680px;
  display: grid;
  place-items: center;
  text-align: center;
  gap: 16px;
  padding: clamp(28px, 6vw, 70px);
  background: #fffdf8;
  border: 10px double #14332f;
  box-shadow: var(--shadow);
}

.certificate-card img {
  width: 130px;
  height: 130px;
  object-fit: contain;
}

.certificate-card h1 {
  margin: 0;
  font-size: clamp(46px, 8vw, 86px);
}

.certificate-card p {
  max-width: 740px;
  margin: 0;
}

.certificate-details {
  display: grid;
  gap: 8px;
  padding: 18px;
  border-top: 1px solid var(--line);
  border-bottom: 1px solid var(--line);
}

.certificate-footer {
  color: var(--muted);
  font-weight: 800;
}

.source-list a {
  display: block;
  padding: 14px 0;
  border-bottom: 1px solid var(--line);
  font-weight: 800;
  color: #0f766e;
}

.source-list span {
  display: block;
  padding: 14px 0;
  border-bottom: 1px solid var(--line);
  font-weight: 800;
  color: #556070;
}

.journey-list {
  display: grid;
  grid-template-columns: repeat(3, minmax(0, 1fr));
  gap: 10px;
  list-style: none;
  padding: 0;
  margin: 0;
}

.journey-list li {
  padding: 13px 14px;
  border: 1px solid var(--line);
  border-radius: 8px;
  background: rgba(255, 255, 255, .82);
  font-weight: 800;
}

.site-footer {
  display: flex;
  justify-content: space-between;
  gap: 20px;
  align-items: center;
  padding: 28px clamp(18px, 5vw, 72px);
  background: #14332f;
  color: #fff;
}

.site-footer p { margin: 4px 0 0; color: rgba(255,255,255,.76); }
.site-footer a { color: #ffe69b; font-weight: 800; }

.footer-links {
  display: grid;
  gap: 8px;
  justify-items: end;
}

@media (max-width: 980px) {
  .hero, .story-hero, .story-layout, .two-grid, .field-grid, .preference-row { grid-template-columns: 1fr; }
  .grid, .story-list, .journey-list, .choice-grid, .portal-stat-grid, .portal-module-grid, .portal-profile-grid, .three-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); }
  .lesson-panel { position: static; }
  .hero-art { min-height: 340px; }
}

@media (max-width: 640px) {
  .site-header { align-items: flex-start; flex-direction: column; }
  .nav { justify-content: flex-start; }
  .grid, .story-list, .journey-list, .choice-grid, .portal-stat-grid, .portal-module-grid, .portal-profile-grid, .three-grid { grid-template-columns: 1fr; }
  .hero { min-height: auto; }
  .site-footer { flex-direction: column; align-items: flex-start; }
  .footer-links { justify-items: start; }
}
'@
Set-Content -Path (Join-Path $assetsDir "site.css") -Value $css -Encoding UTF8

$icon = @'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 96 96" role="img" aria-label="Yuva Club">
  <rect width="96" height="96" rx="20" fill="#14332f"/>
  <circle cx="48" cy="48" r="30" fill="#f59e0b"/>
  <path d="M48 20c10 13 18 21 18 34 0 10-8 18-18 18s-18-8-18-18c0-13 8-21 18-34Z" fill="#fffaf0"/>
  <path d="M31 59c12 8 22 8 34 0-3 13-10 20-17 20s-14-7-17-20Z" fill="#16a34a"/>
</svg>
'@
Set-Content -Path (Join-Path $assetsDir "icon.svg") -Value $icon -Encoding UTF8

$heroSvg = @'
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 900">
  <defs>
    <linearGradient id="g" x1="0" x2="1" y1="0" y2="1">
      <stop stop-color="#fff3ce"/>
      <stop offset=".45" stop-color="#ffd66b"/>
      <stop offset="1" stop-color="#93e0cf"/>
    </linearGradient>
    <pattern id="p" width="84" height="84" patternUnits="userSpaceOnUse">
      <path d="M42 8c16 20 24 32 24 47 0 14-10 23-24 23S18 69 18 55c0-15 8-27 24-47Z" fill="none" stroke="rgba(20,51,47,.22)" stroke-width="4"/>
    </pattern>
  </defs>
  <rect width="1200" height="900" fill="url(#g)"/>
  <rect width="1200" height="900" fill="url(#p)" opacity=".8"/>
  <circle cx="920" cy="210" r="126" fill="rgba(255,255,255,.42)"/>
  <circle cx="238" cy="690" r="184" fill="rgba(255,255,255,.32)"/>
  <path d="M230 270h420c38 0 70 31 70 70v250c0 39-32 70-70 70H230c-39 0-70-31-70-70V340c0-39 31-70 70-70Z" fill="rgba(255,255,255,.82)"/>
  <path d="M250 350h350M250 430h300M250 510h350M250 590h220" stroke="#14332f" stroke-width="28" stroke-linecap="round" opacity=".78"/>
  <path d="M810 410c80-96 176-96 256 0-24 138-232 138-256 0Z" fill="#fffaf0" stroke="#14332f" stroke-width="22"/>
  <circle cx="938" cy="425" r="56" fill="#f59e0b"/>
  <path d="M938 308v-94M938 634v-94M770 474h-94M1200 474h-94M820 356l-68-68M1056 592l-68-68M1056 356l68-68M820 592l-68 68" stroke="#fffaf0" stroke-width="20" stroke-linecap="round" opacity=".9"/>
</svg>
'@
Set-Content -Path (Join-Path $assetsDir "hero.svg") -Value $heroSvg -Encoding UTF8
Set-Content -Path (Join-Path $assetsDir "preview.svg") -Value $heroSvg -Encoding UTF8

foreach ($item in $items) {
  $item.category = Normalize-Global-Category $item.category
}

$orderedItems = @($items | Sort-Object @{ Expression = { Category-Order $_.category } }, @{ Expression = { $_.title } })
$storyCards = ($orderedItems | ForEach-Object { Story-Card $_ "" }) -join "`n"
$featuredCards = (All-Categories | ForEach-Object {
  $category = $_
  $orderedItems | Where-Object { $_.category -eq $category } | Select-Object -First 1
} | Where-Object { $null -ne $_ } | Select-Object -First 8 | ForEach-Object { Story-Card $_ "" }) -join "`n"

$homePage = @()
$homePage += Page-Head "The Global Youth Speaking & Leadership Challenge" "Yuva Club empowers students through presentations, challenges, competitions, mentorship, and leadership growth." ""
$homePage += Site-Header ""
$homePage += @"
  <main>
    <section class="hero">
      <div>
        <p class="eyebrow">yuvaclub.karmabro.com</p>
        <h1>Yuva Club</h1>
        <h2>The Global Youth Speaking & Leadership Challenge</h2>
        <p>Empowering the next generation of confident speakers, critical thinkers, and future leaders through presentations, challenges, competitions, mentorship, and recognition.</p>
        <p><strong>Learn. Speak. Lead. Inspire.</strong></p>
        <div class="hero-actions">
          <a class="button primary" href="registration.php">Join School Yuva</a>
          <a class="button" href="registration.php">Join College Yuva</a>
        </div>
      </div>
      <div class="hero-art" aria-hidden="true"></div>
    </section>

    <section class="band">
      <div class="section-head">
        <h2>What Is Yuva Club?</h2>
        <p>Yuva Club is a global youth leadership platform where students build confidence through public speaking, presentations, research, critical thinking, teamwork, and leadership challenges.</p>
        <p>Inspired by the excitement of academic competitions like the Spelling Bee, Yuva Club gives students opportunities to learn, compete, earn recognition, and grow into future leaders.</p>
      </div>
    </section>

    <section class="band alt">
      <div class="section-head">
        <h2>Our Programs</h2>
      </div>
      <div class="two-grid">
        <div class="feature"><strong>School Yuva</strong><p>Ages 13-17. Helping school students become confident speakers, creative thinkers, and future leaders.</p><p><strong>Journey:</strong> School Yuva Explorer, School Yuva Speaker, School Yuva Leader, School Yuva Mentor.</p></div>
        <div class="feature"><strong>College Yuva</strong><p>Ages 18-21. Preparing college students for leadership, careers, innovation, entrepreneurship, and community impact.</p><p><strong>Journey:</strong> College Yuva Explorer, College Yuva Speaker, College Yuva Leader, College Yuva Mentor.</p></div>
      </div>
    </section>

    <section class="band">
      <div class="section-head">
        <h2>The Yuva Leadership Challenge</h2>
        <p>Every member progresses through speaking and leadership challenges that build confidence, communication, and character.</p>
      </div>
      <div class="grid">
        <div class="feature"><strong>Monthly Challenges</strong><p>Public speaking, research presentations, leadership activities, debate and discussion, team challenges, and AI presentation coaching.</p></div>
        <div class="feature"><strong>Championship Journey</strong><p>Club Challenge, Regional Challenge, State Challenge, National Challenge, and International Yuva Championship.</p></div>
        <div class="feature"><strong>Scoring Rubric</strong><p>Students are evaluated on confidence, clarity, research, organization, creativity, visuals, engagement, Q&A, leadership, and timing.</p></div>
        <div class="feature"><strong>Mentorship</strong><p>Mentors and judges help students improve with useful feedback and clear next steps.</p></div>
      </div>
    </section>

    <section class="band">
      <div class="section-head">
        <h2>Learn From Inspiring Topics</h2>
        <p>Students choose from hundreds of topics or propose their own approved topic.</p>
      </div>
      <ul class="journey-list">
        <li>Great World Leaders</li>
        <li>Science, Technology, and Artificial Intelligence</li>
        <li>Space Exploration</li>
        <li>Entrepreneurs and Innovators</li>
        <li>Environmental Sustainability</li>
        <li>Ancient Civilizations and World History</li>
        <li>Financial Literacy and Career Exploration</li>
        <li>Health, Wellness, Communication, Culture, Heritage, and Current Events</li>
      </ul>
    </section>

    <section class="band">
      <div class="section-head">
        <h2>Earn Recognition</h2>
      </div>
      <div class="grid">
        <div class="feature"><strong>Digital Badges</strong><p>Celebrate milestones, consistency, speaking growth, and challenge achievements.</p></div>
        <div class="feature"><strong>Certificates</strong><p>Earn certificates for levels, participation, presentations, and challenge stages.</p></div>
        <div class="feature"><strong>Leadership Points</strong><p>Build a visible record of attendance, presentations, questions, service, and rubric scores.</p></div>
        <div class="feature"><strong>Digital Leadership Portfolio</strong><p>Track presentations, topics, speaking minutes, feedback, awards, certificates, and service hours over time.</p></div>
      </div>
    </section>

    <section class="band alt">
      <div class="section-head">
        <h2>Why Join Yuva Club?</h2>
        <p>Build confidence in public speaking, strengthen communication, improve critical thinking, learn research techniques, practice teamwork, receive AI-powered coaching, earn certificates, and prepare for college, careers, and community leadership.</p>
      </div>
      <img class="topics-poster" src="assets/topics-source.png" alt="Yuva Club topics poster showing global leaders, scientists, entrepreneurs, civilizations, monuments, technology, service, stories, nature, and life skills.">
    </section>

    <section class="band">
      <div class="section-head">
        <h2>How It Works</h2>
      </div>
      <div class="grid">
        <div class="feature"><strong>1. Register</strong><p>Create your Yuva Club account.</p></div>
        <div class="feature"><strong>2. Choose Your Challenge</strong><p>Select an exciting topic or challenge.</p></div>
        <div class="feature"><strong>3. Research and Practice</strong><p>Use AI tools and trusted resources to prepare.</p></div>
        <div class="feature"><strong>4. Present</strong><p>Deliver your presentation live.</p></div>
        <div class="feature"><strong>5. Receive Feedback</strong><p>Get AI insights and mentor evaluations.</p></div>
        <div class="feature"><strong>6. Earn Points and Advance</strong><p>Progress through the Leadership Journey and qualify for higher-level challenges.</p></div>
      </div>
    </section>

    <section class="band">
      <div class="section-head">
        <h2>Our Mission</h2>
        <p>To inspire and empower young people around the world to become confident speakers, lifelong learners, compassionate leaders, and responsible global citizens through meaningful learning experiences, leadership challenges, and mentorship.</p>
      </div>
    </section>

    <section class="band alt">
      <div class="section-head">
        <h2>Your Leadership Journey Starts Here.</h2>
        <p><strong>Learn. Speak. Lead. Inspire.</strong></p>
        <div class="hero-actions">
          <a class="button primary" href="registration.php">Join School Yuva</a>
          <a class="button" href="registration.php">Join College Yuva</a>
        </div>
      </div>
    </section>
  </main>
"@
$homePage += Site-Footer ""
Set-Content -Path (Join-Path $siteRoot "index.html") -Value ($homePage -join "`n") -Encoding UTF8

$challengesPage = @()
$challengesPage += Page-Head "Leadership Challenge" "Yuva Club challenge stages, scoring rubric, recognition, and championship pathway." ""
$challengesPage += Site-Header ""
$challengesPage += @"
  <main>
    <section class="band">
      <div class="section-head">
        <p class="eyebrow">Leadership Challenge</p>
        <h1>The Global Youth Speaking & Leadership Challenge</h1>
        <p>Yuva Club turns public speaking practice into a structured leadership journey with monthly challenges, mentor feedback, AI coaching, points, certificates, and advancement opportunities.</p>
        <div class="hero-actions">
          <a class="button primary" href="registration.php">Join the Challenge</a>
          <a class="button" href="leaderboard.php">View Leaderboard</a>
        </div>
      </div>
    </section>
    <section class="band alt">
      <div class="section-head">
        <h2>Challenge Pathway</h2>
      </div>
      <div class="challenge-path">
        <span class="active">Practice Session</span>
        <span>Monthly Club Challenge</span>
        <span>Regional Challenge</span>
        <span>State Challenge</span>
        <span>National Challenge</span>
        <span>International Yuva Championship</span>
      </div>
    </section>
    <section class="band">
      <div class="section-head">
        <h2>Monthly Challenge Types</h2>
      </div>
      <div class="grid">
        <div class="feature"><strong>Public Speaking</strong><p>Present with confidence, clear voice, strong structure, and audience awareness.</p></div>
        <div class="feature"><strong>Research Presentations</strong><p>Use trusted sources, organize ideas, explain evidence, and answer questions.</p></div>
        <div class="feature"><strong>Leadership Activities</strong><p>Lead discussions, support peers, organize tasks, and practice responsibility.</p></div>
        <div class="feature"><strong>Debate and Discussion</strong><p>Listen carefully, respond respectfully, and explain your reasoning.</p></div>
        <div class="feature"><strong>Team Challenges</strong><p>Collaborate on a shared problem, project, or presentation.</p></div>
        <div class="feature"><strong>AI Presentation Coaching</strong><p>Use AI feedback as a practice tool while mentors approve official scores.</p></div>
      </div>
    </section>
    <section class="band">
      <div class="section-head">
        <h2>Scoring Rubric</h2>
        <p>Judges score each category from 1 to 10 for a total of 100 points.</p>
      </div>
      <div class="rubric-grid">
        <div><strong>Confidence</strong><span>Poise, preparation, and presence.</span></div>
        <div><strong>Voice Clarity</strong><span>Volume, pace, pronunciation, and expression.</span></div>
        <div><strong>Research Quality</strong><span>Accuracy, sources, and depth of understanding.</span></div>
        <div><strong>Organization</strong><span>Clear opening, body, transitions, and conclusion.</span></div>
        <div><strong>Creativity</strong><span>Original thinking, examples, and memorable delivery.</span></div>
        <div><strong>Visual Presentation</strong><span>Slides, props, images, or structure that supports learning.</span></div>
        <div><strong>Audience Engagement</strong><span>Eye contact, questions, interaction, and listener awareness.</span></div>
        <div><strong>Question Handling</strong><span>Thoughtful answers and calm response under pressure.</span></div>
        <div><strong>Leadership</strong><span>Responsibility, respect, encouragement, and initiative.</span></div>
        <div><strong>Time Management</strong><span>Finishing within the expected time and pacing well.</span></div>
      </div>
    </section>
    <section class="band alt">
      <div class="section-head">
        <h2>Recognition</h2>
        <p>Students can earn digital badges, certificates, leadership points, achievement awards, mentor recognition, and a digital leadership portfolio that grows over time.</p>
      </div>
    </section>
  </main>
"@
$challengesPage += Site-Footer ""
Set-Content -Path (Join-Path $siteRoot "challenges.html") -Value ($challengesPage -join "`n") -Encoding UTF8

$appTopicCards = foreach ($category in (All-Categories)) {
  $description = Escape-Html (Category-Description $category)
  $categoryItems = @($orderedItems | Where-Object { $_.category -eq $category })
  $links = if ($categoryItems.Count -gt 0) {
    ($categoryItems | ForEach-Object { "<a href=`"pages/$($_.slug).html`">$((Escape-Html $_.title))</a>" }) -join ""
  } else {
    (Category-Suggestions $category | ForEach-Object { "<span>$((Escape-Html $_))</span>" }) -join ""
  }
@"
        <div class="month-card">
          <strong>$((Escape-Html $category))</strong>
          <p>$description</p>
          <div class="source-list">$links</div>
        </div>
"@
}

$appPage = @()
$appPage += Page-Head "YUVA Club App" "Install YUVA Club on Android and iPhone and use the student leadership portal as an app." ""
$appPage += Site-Header ""
$appPage += @"
  <main>
    <section class="band">
      <div class="section-head">
        <p class="eyebrow">Android & iPhone</p>
        <h1>YUVA Club App</h1>
        <p>Install YUVA Club on your phone and use it as the official hub for registration, challenges, presentations, Zoom sessions, topic research, scoring, leadership profiles, points, tokens, certificates, rewards, and parent progress tracking.</p>
        <div class="hero-actions">
          <a class="button primary" href="portal-login.php">Open Student Portal</a>
          <a class="button" href="registration.php">Register Student</a>
        </div>
      </div>
      <div class="portal-module-grid">
        <div class="feature"><strong>Challenge Dashboard</strong><p>Students can see program level, challenge stage, rubric score, sessions, topics, Zoom information, points, tokens, badges, and certificates.</p></div>
        <div class="feature"><strong>Leadership Profile</strong><p>The profile grows over time with presentations, speaking minutes, topics, feedback, milestones, and service hours.</p></div>
        <div class="feature"><strong>Parent Dashboard</strong><p>Parents can view progress, presentations, attendance, rewards, feedback, certificates, and recordings.</p></div>
        <div class="feature"><strong>Admin Controls</strong><p>Admins can approve students, assign sessions, manage topics, create challenge stages, score rubric categories, add points, tokens, awards, feedback, and certificates.</p></div>
        <div class="feature"><strong>Safe by Design</strong><p>No private student chat, parent contact connected to each account, admin approval, adult moderation, and a report issue button.</p></div>
        <div class="feature"><strong>Global Topic Library</strong><p>Students can choose topics in leadership, science, culture, business, history, health, sports, digital skills, STEM, and careers.</p></div>
      </div>
    </section>

    <section class="band alt">
      <div class="section-head">
        <p class="eyebrow">Same Library as Website</p>
        <h2>Full App Topic Library</h2>
        <p>The app uses the same topic library as the Yuva Club website. Students can open any topic page, prepare presentations, and submit their selected topic from the Student Portal.</p>
      </div>
      <div class="grid">
$($appTopicCards -join "`n")
      </div>
    </section>

    <section class="band">
      <div class="section-head">
        <h2>Install on Android</h2>
        <p>Open yuvaclub.karmabro.com in Chrome, tap the menu, then choose Install App or Add to Home screen. The app icon will appear on the phone like a normal app.</p>
      </div>
      <div class="section-head">
        <h2>Install on iPhone or iPad</h2>
        <p>Open yuvaclub.karmabro.com in Safari, tap Share, then choose Add to Home Screen. Use the YUVA Club icon to open the app.</p>
      </div>
    </section>
  </main>
"@
$appPage += Site-Footer ""
Set-Content -Path (Join-Path $siteRoot "app.html") -Value ($appPage -join "`n") -Encoding UTF8

$programsPage = @()
$programsPage += Page-Head "Programs" "Yuva Club membership groups and leadership journey." ""
$programsPage += Site-Header ""
$programsPage += @"
  <main>
    <section class="band">
      <div class="section-head">
        <p class="eyebrow">Membership Groups</p>
        <h1>Yuva Club Programs</h1>
        <p>Yuva Club helps students grow through research, presentations, discussion, service, mentorship, and leadership challenges. Members belong to an age-aware program group and progress through a challenge-style leadership journey.</p>
      </div>
      <div class="two-grid">
        <div class="feature"><strong>School Yuva</strong><p>Ages 13-17. For school students building public speaking, research, leadership, discussion, confidence, and service habits.</p></div>
        <div class="feature"><strong>College Yuva</strong><p>Ages 18-21. For college-age members focused on advanced presentations, mentoring, innovation, entrepreneurship, career preparation, and community leadership.</p></div>
      </div>
    </section>
    <section class="band alt">
      <div class="section-head">
        <p class="eyebrow">Leadership Journey</p>
        <h2>Explorer to Mentor</h2>
        <p>Points and AI feedback can support growth, but level advancement requires admin, mentor, or judge approval so certificates and awards remain meaningful.</p>
      </div>
      <div class="grid">
        <div class="feature"><strong>Explorer</strong><p>Learn, participate, build confidence, and complete onboarding.</p></div>
        <div class="feature"><strong>Speaker</strong><p>Present topics, answer questions, improve communication, and submit research.</p></div>
        <div class="feature"><strong>Leader</strong><p>Lead discussions, collaborate with teams, support sessions, and demonstrate consistency.</p></div>
        <div class="feature"><strong>Mentor</strong><p>Guide new members, provide feedback, inspire others, and represent Yuva Club values.</p></div>
      </div>
    </section>
  </main>
"@
$programsPage += Site-Footer ""
Set-Content -Path (Join-Path $siteRoot "programs.html") -Value ($programsPage -join "`n") -Encoding UTF8

$safetyPage = @()
$safetyPage += Page-Head "Safety" "Yuva Club youth safety, privacy, and parent trust practices." ""
$safetyPage += Site-Header ""
$safetyPage += @"
  <main>
    <section class="band">
      <div class="section-head">
        <p class="eyebrow">Parent Trust</p>
        <h1>Safety at Yuva Club</h1>
        <p>Yuva Club is designed for supervised learning. Students practice leadership in a structured environment with parent awareness, admin oversight, Zoom safety controls, and human review of AI-generated feedback.</p>
      </div>
      <div class="grid">
        <div class="feature"><strong>Parent Consent</strong><p>School-age members need parent or guardian permission and parent contact information connected to their account.</p></div>
        <div class="feature"><strong>No Private Student Chat</strong><p>The platform is built around supervised sessions, presentations, and approved educational activities.</p></div>
        <div class="feature"><strong>Protected Zoom Links</strong><p>Zoom links, meeting IDs, and passwords appear inside the approved student dashboard, not as public website links.</p></div>
        <div class="feature"><strong>Adult Moderation</strong><p>Admins and mentors can monitor sessions, approve students, review submissions, and handle safety reports.</p></div>
        <div class="feature"><strong>AI With Human Approval</strong><p>AI Coach drafts feedback and points, but admins approve official scores, rank changes, certificates, and sensitive feedback.</p></div>
        <div class="feature"><strong>Safety Reporting</strong><p>Students can report concerns from their dashboard, and parents can contact Yuva Club for support.</p></div>
      </div>
    </section>
  </main>
"@
$safetyPage += Site-Footer ""
Set-Content -Path (Join-Path $siteRoot "safety.html") -Value ($safetyPage -join "`n") -Encoding UTF8

$groupedLookup = @{}
foreach ($group in ($items | Group-Object category)) {
  $groupedLookup[$group.Name] = $group.Group
}
$curriculumCards = foreach ($category in (All-Categories)) {
  $description = Escape-Html (Category-Description $category)
  $categoryItems = $groupedLookup[$category]
  if ($categoryItems) {
    $links = ($categoryItems | ForEach-Object { "<a href=`"pages/$($_.slug).html`">$((Escape-Html $_.title))</a>" }) -join ""
  } else {
    $links = (Category-Suggestions $category | ForEach-Object { "<span>$((Escape-Html $_))</span>" }) -join ""
  }
@"
        <div class="month-card">
          <strong>$((Escape-Html $category))</strong>
          <p>$description</p>
          <div class="source-list">$links</div>
        </div>
"@
}

$curriculum = @()
$curriculum += Page-Head "Topics We Explore" "A flexible global Yuva Club learning journey for leadership, curiosity, public speaking, research, and student presentations." ""
$curriculum += Site-Header ""
$curriculum += @"
  <main>
    <section class="band">
      <div class="section-head">
        <p class="eyebrow">Learning Journey</p>
        <h1>Topics We Explore</h1>
        <p>A flexible global journey through leaders, civilizations, discoveries, cultures, monuments, technology, stories, nature, service, communication, careers, and life skills. Students can present people, places, books, bridges, satellites, discoveries, parks, inventions, startups, volunteer organizations, and more.</p>
      </div>
      <div class="grid">
$($curriculumCards -join "`n")
      </div>
    </section>
  </main>
"@
$curriculum += Site-Footer ""
Set-Content -Path (Join-Path $siteRoot "curriculum.html") -Value ($curriculum -join "`n") -Encoding UTF8

$stories = @()
$stories += Page-Head "Inspirational Stories and Leaders" "All Yuva Club story, leader, innovator, and tradition pages." ""
$stories += Site-Header ""
$stories += @"
  <main>
    <section class="band">
      <div class="section-head">
        <p class="eyebrow">Reading Library</p>
        <h1>Inspirational Stories and Leaders</h1>
        <p>Each page is written for children and includes vocabulary, discussion questions, and leadership-focused presentation prompts.</p>
      </div>
      <div class="story-list">
$storyCards
      </div>
    </section>
  </main>
"@
$stories += Site-Footer ""
Set-Content -Path (Join-Path $siteRoot "stories.html") -Value ($stories -join "`n") -Encoding UTF8

$resources = @()
$resources += Page-Head "Resources" "Recommended source and reading resources for Yuva Club." ""
$resources += Site-Header ""
$resources += @"
  <main>
    <section class="band">
      <div class="section-head">
        <p class="eyebrow">Research & Reading</p>
        <h1>Resources</h1>
        <p>These are useful references for parents and organizers. The weekly pages on this site are original, age-appropriate summaries for discussion.</p>
      </div>
      <div class="two-grid">
        <div class="resource-card"><strong>Mahabharata & Ramayana</strong><p>Amar Chitra Katha, Project Gutenberg, ValmikiRamayan.net, Wisdom Library, and Sacred Texts can help families explore deeper versions.</p></div>
        <div class="resource-card"><strong>Vivekananda & Kalam</strong><p>Use Belur Math, Vedanta Society, AbdulKalam.com, DRDO, ISRO, and Britannica for biography references.</p></div>
        <div class="resource-card"><strong>Panchatantra</strong><p>StoryWeaver, Panchatantra collections, and public-domain retellings are useful for moral stories and discussion.</p></div>
        <div class="resource-card"><strong>Traditions & Festivals</strong><p>Hindu American Foundation, Hinduism Today, Chinmaya Mission, and Vedic Heritage Portal are useful for background reading.</p></div>
        <div class="resource-card"><strong>Innovators & Entrepreneurs</strong><p>Use official company biographies, reputable museum resources, foundation pages, Nobel Prize profiles, Britannica, and library resources for age-appropriate background.</p></div>
        <div class="resource-card"><strong>Student Heroes</strong><p>Families can help students choose a hero from family, community, history, science, service, arts, or daily life.</p></div>
        <div class="resource-card"><strong>Environment & Nature</strong><p>Use NOAA, NASA Climate Kids, National Park Service, local park agencies, and conservation education resources for science-based background.</p></div>
        <div class="resource-card"><strong>Community & Service</strong><p>Use official public service websites, local nonprofit pages, AmeriCorps, libraries, schools, and community organizations for real examples of service leadership.</p></div>
        <div class="resource-card"><strong>Life Skills</strong><p>Use CFPB Money as You Grow, Toastmasters youth speaking resources, school counseling resources, and trusted education sites for practical skill-building ideas.</p></div>
        <div class="resource-card"><strong>Monuments & Architecture</strong><p>Use UNESCO World Heritage Centre, official museum resources, national heritage agencies, and reputable architecture/history references for monument research.</p></div>
        <div class="resource-card"><strong>Space & Technology</strong><p>Use NASA, ISRO, ESA, reputable science museums, National Institute of Standards and Technology AI resources, and robotics education sites for mission and technology research.</p></div>
      </div>
    </section>
  </main>
"@
$resources += Site-Footer ""
Set-Content -Path (Join-Path $siteRoot "resources.html") -Value ($resources -join "`n") -Encoding UTF8

foreach ($item in $items) {
  $symbol = (Escape-Html $item.title.Substring(0,1))
  $displayReadTime = "3-5 min"
  $topicImage = Topic-Image $item
  $heroVisual = if ($topicImage) {
    "<div class=`"story-symbol story-photo`" aria-hidden=`"true`" style=`"width:560px;max-width:100%;height:420px;display:block;overflow:hidden;padding:0;justify-self:center;`"><img src=`"../assets/topics/$topicImage`" alt=`"`" style=`"width:100%;height:100%;display:block;object-fit:cover;`"></div>"
  } else {
    "<div class=`"story-symbol`" aria-hidden=`"true`"><span>$symbol</span></div>"
  }
  $vocab = ($item.vocabulary | ForEach-Object { "<li>$(Escape-Html $_)</li>" }) -join "`n"
  $expandedReading = Expand-Reading $item
  $reading = ($expandedReading | ForEach-Object { "<p>$(Escape-Html $_)</p>" }) -join "`n"
  $expandedQuestions = Expand-Questions $item
  $questions = ($expandedQuestions | ForEach-Object { "<li>$(Escape-Html (Open-Discussion-Question $_))</li>" }) -join "`n"
  $optionalChallenge = Escape-Html (Optional-Challenge $item)
  $topicMatters = Escape-Html (Topic-Matters $item)
  $questionFieldId = "student_question_$($item.slug)"
  $page = @()
  $page += Page-Head $item.title $item.subtitle "../"
  $page += Site-Header "../"
  $page += @"
  <main>
    <section class="story-hero">
      <div>
        <span class="story-badge">$((Escape-Html $item.category)) - $((Escape-Html $item.type))</span>
        <h1>$((Escape-Html $item.title))</h1>
        <p>$((Escape-Html $item.subtitle))</p>
        <div class="button-row">
          <a class="button primary" href="#reading">Read Page</a>
          <a class="button" href="../stories.html">All Stories</a>
        </div>
      </div>
$heroVisual
    </section>

    <section class="story-layout" id="reading">
      <aside class="lesson-panel">
        <h2>Session Guide</h2>
        <div class="meta-list">
          <div><span>Reading Time</span><strong>$displayReadTime</strong></div>
          <div><span>Leadership Skill</span><strong>$((Escape-Html $item.skill))</strong></div>
          <div><span>Presenter Prompt</span><strong>$((Escape-Html $item.activity))</strong></div>
        </div>
      </aside>
      <article class="reading-panel">
        <h2>Why This Topic Matters</h2>
        <p>$topicMatters</p>
        <h2>Reading</h2>
$reading
        <h2>Vocabulary</h2>
        <ul class="pill-list">
$vocab
        </ul>
        <h2>Discussion Questions</h2>
        <ol class="question-list">
$questions
        </ol>
        <h2>Leadership Takeaway</h2>
        <p><strong>$((Escape-Html $item.skill)):</strong> $((Escape-Html $item.activity))</p>
        <div class="challenge-box">
          <h2>Optional Challenge</h2>
          <p>$optionalChallenge</p>
        </div>
        <div class="student-question-box">
          <h2>Student-Created Question</h2>
          <label for="$questionFieldId">Write one question you want to ask the group.</label>
          <textarea id="$questionFieldId" name="$questionFieldId" placeholder="Example: What would you have done differently, and why?"></textarea>
        </div>
      </article>
    </section>
  </main>
"@
  $page += Site-Footer "../"
  Set-Content -Path (Join-Path $pagesDir "$($item.slug).html") -Value ($page -join "`n") -Encoding UTF8
}

Write-Host "Built Yuva Club site with $($items.Count) reading pages at $siteRoot"
